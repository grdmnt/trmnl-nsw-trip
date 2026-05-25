#!/usr/bin/env python3
"""
TfNSW Trip Planner - E-ink Display

Compact timetable for West Ryde to Town Hall.

Usage:
    export TFNSW_API_KEY='your-api-key'
    python tnsw.py
"""

import os
import json
import urllib.request
import urllib.parse
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Any

# Configuration
API_KEY = os.environ.get('TFNSW_API_KEY', '')
API_ENDPOINT = 'https://api.transport.nsw.gov.au/v1/tp/'

# Routes from West Ryde
ROUTES = [
    {
        'name': 'Town Hall',
        'origin': '211420',
        'destination': '200070',
        'dest_type': 'stop',
    },
    {
        'name': 'Ashfield',
        'origin': '211420',
        'destination': '213110',
        'dest_type': 'stop',
    },
    {
        'name': 'Top Ryde',
        'origin': '211420',
        'destination': 'poiID:858285682:95352012:-1:Top Ryde City:Ryde:Top Ryde City:ANY:POI:4880201:3748992:GDAV:nsw',
        'dest_type': 'poi',
        'bus_only': True,
    },
    {
        'name': 'Crows Nest',
        'origin': '211420',
        'destination': '206516',
        'dest_type': 'stop',
        'show_legs': True,
    },
]


def make_api_request(api_call: str, params: dict) -> Optional[dict]:
    """Make an API request to TfNSW."""
    url = API_ENDPOINT + api_call + '?' + urllib.parse.urlencode(params)
    
    request = urllib.request.Request(url)
    request.add_header('Authorization', f'apikey {API_KEY}')
    request.add_header('Accept', 'application/json')
    
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        print(f"  HTTP Error {e.code}: {e.reason}")
        return None
    except urllib.error.URLError as e:
        print(f"  URL Error: {e.reason}")
        return None
    except Exception as e:
        print(f"  Error: {e}")
        return None


def get_trips(route: dict, departure_time: datetime = None) -> List[dict]:
    """Get trip options for a route."""
    if departure_time is None:
        departure_time = datetime.now()

    params = {
        'outputFormat': 'rapidJSON',
        'coordOutputFormat': 'EPSG:4326',
        'depArrMacro': 'dep',
        'itdDate': departure_time.strftime('%Y%m%d'),
        'itdTime': departure_time.strftime('%H%M'),
        'type_origin': 'stop',
        'name_origin': route['origin'],
        'type_destination': route.get('dest_type', 'stop'),
        'name_destination': route['destination'],
        'calcNumberOfTrips': 15,
    }

    if route.get('bus_only'):
        params['exclMOT_1'] = '1'

    result = make_api_request('trip', params)

    if result:
        return result.get('journeys', [])
    return []


def format_time(time_str: str) -> str:
    """Format ISO time string to h:MMam/pm in local timezone."""
    if not time_str:
        return '?:??'
    try:
        dt = datetime.fromisoformat(time_str.replace('Z', '+00:00'))
        local_dt = dt.astimezone()
        return local_dt.strftime('%-I:%M%p').lower()
    except:
        return time_str[:5] if len(time_str) >= 5 else time_str


def parse_time(time_str: str) -> Optional[datetime]:
    """Parse ISO time string to datetime in local timezone."""
    if not time_str:
        return None
    try:
        dt = datetime.fromisoformat(time_str.replace('Z', '+00:00'))
        # Convert to local timezone, then make naive for comparison with datetime.now()
        local_dt = dt.astimezone()
        return local_dt.replace(tzinfo=None)
    except:
        return None


def get_mode(journey: dict) -> str:
    """Get the primary transport mode for a journey (Train or Bus)."""
    legs = journey.get('legs', [])
    for leg in legs:
        transportation = leg.get('transportation', {})
        product = transportation.get('product', {})
        mode_class = product.get('class', 99)
        if mode_class == 1:
            return 'Train'
        elif mode_class == 5:
            return 'Bus'
    return 'Other'


def get_journey_times(journey: dict) -> tuple:
    """Extract departure, arrival times and duration from journey."""
    legs = journey.get('legs', [])
    if not legs:
        return None, None, None

    first_origin = legs[0].get('origin', {})
    last_dest = legs[-1].get('destination', {})

    dep = first_origin.get('departureTimeEstimated') or first_origin.get('departureTimePlanned', '')
    arr = last_dest.get('arrivalTimeEstimated') or last_dest.get('arrivalTimePlanned', '')

    # Calculate duration from actual departure and arrival times
    dep_dt = parse_time(dep)
    arr_dt = parse_time(arr)
    if dep_dt and arr_dt:
        duration = int((arr_dt - dep_dt).total_seconds() // 60)
    else:
        duration = sum(leg.get('duration', 0) for leg in legs) // 60

    return format_time(dep), format_time(arr), duration


def get_leg_info(journey: dict) -> str:
    """Extract leg information for a journey (interchange stations and times)."""
    legs = journey.get('legs', [])
    leg_parts = []

    for leg in legs:
        transportation = leg.get('transportation', {})
        product = transportation.get('product', {})
        mode_class = product.get('class', 99)

        # Skip walking legs
        if mode_class in [99, 100]:
            continue

        origin = leg.get('origin', {})
        dest = leg.get('destination', {})

        dep_time = origin.get('departureTimeEstimated') or origin.get('departureTimePlanned', '')
        arr_time = dest.get('arrivalTimeEstimated') or dest.get('arrivalTimePlanned', '')

        # Get short station names
        origin_name = origin.get('disassembledName', origin.get('name', ''))
        dest_name = dest.get('disassembledName', dest.get('name', ''))

        # Shorten station names
        origin_short = origin_name.replace(' Station', '').replace(', Platform', ' P').split(',')[0]
        dest_short = dest_name.replace(' Station', '').replace(', Platform', ' P').split(',')[0]

        leg_parts.append(f"{format_time(dep_time)} {origin_short} > {format_time(arr_time)} {dest_short}")

    return ' | '.join(leg_parts)


def fetch_journeys(route: dict) -> List[dict]:
    """Fetch all journeys for a route for the next 2 hours."""
    current_time = datetime.now()
    end_time = current_time + timedelta(hours=2)
    all_journeys = []
    seen = set()

    search_time = current_time

    for _ in range(3):
        if search_time >= end_time:
            break

        journeys = get_trips(route, search_time)

        if not journeys:
            break

        for journey in journeys:
            legs = journey.get('legs', [])
            if not legs:
                continue

            first_origin = legs[0].get('origin', {})
            dep_str = first_origin.get('departureTimePlanned', '')

            # Unique key
            lines = [leg.get('transportation', {}).get('number', 'walk') for leg in legs]
            key = f"{dep_str}-{'-'.join(lines)}"

            if key in seen:
                continue
            seen.add(key)

            dep_dt = parse_time(dep_str)
            if dep_dt:
                if dep_dt > end_time or dep_dt < current_time - timedelta(minutes=2):
                    continue

            all_journeys.append(journey)

        # Advance search
        last = journeys[-1]
        last_legs = last.get('legs', [])
        if last_legs:
            last_dep = last_legs[0].get('origin', {}).get('departureTimePlanned', '')
            last_dt = parse_time(last_dep)
            if last_dt:
                search_time = last_dt + timedelta(minutes=1)
            else:
                break
        else:
            break

    # Sort by departure
    all_journeys.sort(key=lambda j: j.get('legs', [{}])[0].get('origin', {}).get('departureTimePlanned', ''))

    return all_journeys


def display_route(route: dict):
    """Display timetable for a single route."""
    journeys = fetch_journeys(route)

    print(f"West Ryde > {route['name']}")

    if not journeys:
        print("  No trips")
        print()
        return

    show_legs = route.get('show_legs', False)

    # Group by mode
    trains = [j for j in journeys if get_mode(j) == 'Train']
    buses = [j for j in journeys if get_mode(j) == 'Bus']

    for mode, trips in [('TRAIN', trains), ('BUS', buses)]:
        if not trips:
            continue

        # Next departure (featured)
        dep, arr, mins = get_journey_times(trips[0])
        print(f"{mode}: {dep} > {arr} ({mins}m)")

        # Show legs for first trip if enabled
        if show_legs:
            leg_info = get_leg_info(trips[0])
            if leg_info:
                print(f"  {leg_info}")

        # Next 4 departures as subtext
        upcoming = []
        for t in trips[1:5]:
            d, a, m = get_journey_times(t)
            upcoming.append(f"{d}>{a}")

        if upcoming:
            print(f"  {', '.join(upcoming)}")

    print()


def main():
    """Main function - compact eink output."""
    if not API_KEY:
        print("No API key. Set TFNSW_API_KEY.")
        return

    print(f"{datetime.now().strftime('%-I:%M%p').lower()}")
    print()

    for route in ROUTES:
        display_route(route)


if __name__ == '__main__':
    main()
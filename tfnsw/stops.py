#!/usr/bin/env python3
"""
TfNSW Stop Finder - Find stop IDs for specific locations

This script searches for stop information for a predefined list of locations.
Useful for setting up route configurations.

Usage:
    export TFNSW_API_KEY='your-api-key'
    python find_stops.py
"""

import os
import json
import urllib.request
import urllib.parse
from typing import Optional, List

# Configuration
API_KEY = os.environ.get('TFNSW_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJOMG82THhrQWV1NGwzQUpPMTFTRi1taURPcHdDZjlNOU5zTzZQcExiWWNvIiwiaWF0IjoxNzcwMjc4NjIyfQ.lawyWV-0FYdpBraCpeylKv-ZxuUi4Uo_W4rHqJyoPg8')
API_ENDPOINT = 'https://api.transport.nsw.gov.au/v1/tp/'

# Locations to search for
LOCATIONS = [
    'West Ryde',
    'Crows Nest'
]

# Transport mode mapping
TRANSPORT_MODES = {
    1: 'Train',
    4: 'Light Rail',
    5: 'Bus',
    7: 'Coach',
    9: 'Ferry',
    11: 'School Bus'
}

MODE_ICONS = {
    1: '🚆',
    4: '🚊',
    5: '🚌',
    7: '🚐',
    9: '⛴️',
    11: '🚌'
}


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
        print(f"HTTP Error {e.code}: {e.reason}")
        if e.code == 401:
            print("Authentication failed. Please check your API key.")
        return None
    except urllib.error.URLError as e:
        print(f"URL Error: {e.reason}")
        return None
    except json.JSONDecodeError as e:
        print(f"JSON Decode Error: {e}")
        return None


def search_stop(query: str) -> List[dict]:
    """Search for stops matching a query."""
    params = {
        'outputFormat': 'rapidJSON',
        'type_sf': 'any',
        'name_sf': query,
        'coordOutputFormat': 'EPSG:4326',
        'TfNSWSF': 'true',
        'anyMaxSizeHitList': 10
    }
    
    result = make_api_request('stop_finder', params)
    if result and 'locations' in result:
        return result['locations']
    return []


def get_modes_string(modes: List[int]) -> str:
    """Convert mode IDs to readable string with icons."""
    mode_strings = []
    for mode_id in modes:
        icon = MODE_ICONS.get(mode_id, '❓')
        name = TRANSPORT_MODES.get(mode_id, f'Mode {mode_id}')
        mode_strings.append(f"{icon} {name}")
    return ', '.join(mode_strings) if mode_strings else 'N/A'


def find_best_stop(locations: List[dict], search_term: str) -> Optional[dict]:
    """Find the best matching stop from search results."""
    # First, look for the one marked as best
    for loc in locations:
        if loc.get('isBest'):
            return loc
    
    # Otherwise, prefer stops over other types
    stops = [loc for loc in locations if loc.get('type') == 'stop']
    if stops:
        return stops[0]
    
    # Fall back to first result
    return locations[0] if locations else None


def main():
    print("=" * 80)
    print("TfNSW Stop Finder")
    print("=" * 80)
    
    if not API_KEY:
        print("\n⚠️  No API key configured!")
        print("   Set the TFNSW_API_KEY environment variable.")
        print("   Get your free API key at: https://opendata.transport.nsw.gov.au/")
        return
    
    print(f"\nSearching for {len(LOCATIONS)} location(s)...\n")
    
    found_stops = []
    
    for location in LOCATIONS:
        print(f"{'─' * 80}")
        print(f"🔍 Searching for: {location}")
        print(f"{'─' * 80}")
        
        results = search_stop(location)
        
        if not results:
            print(f"   ❌ No results found for '{location}'\n")
            continue
        
        # Get best match
        best = find_best_stop(results, location)
        
        if best:
            stop_id = best.get('id', 'N/A')
            name = best.get('name', 'Unknown')
            stop_type = best.get('type', 'unknown')
            modes = best.get('modes', [])
            coord = best.get('coord', [])
            
            print(f"\n   ✅ Best Match:")
            print(f"      Name: {name}")
            print(f"      ID: {stop_id}")
            print(f"      Type: {stop_type}")
            print(f"      Modes: {get_modes_string(modes)}")
            if coord and len(coord) >= 2:
                print(f"      Coordinates: {coord[0]}, {coord[1]}")
            
            found_stops.append({
                'search_term': location,
                'id': stop_id,
                'name': name,
                'type': stop_type,
                'modes': modes,
                'coord': coord
            })
        
        # Show other options
        other_stops = [loc for loc in results if loc.get('type') == 'stop' and loc != best][:3]
        if other_stops:
            print(f"\n   📋 Other stop options:")
            for stop in other_stops:
                stop_id = stop.get('id', 'N/A')
                name = stop.get('name', 'Unknown')
                modes = stop.get('modes', [])
                print(f"      • {name}")
                print(f"        ID: {stop_id} | Modes: {get_modes_string(modes)}")
        
        print()
    
    # Summary
    print("=" * 80)
    print("SUMMARY - Found Stops")
    print("=" * 80)
    print()
    
    # Table format
    print(f"{'Location':<15} {'Stop ID':<12} {'Name':<40} {'Modes'}")
    print(f"{'-'*15} {'-'*12} {'-'*40} {'-'*20}")
    
    for stop in found_stops:
        modes_short = ', '.join([TRANSPORT_MODES.get(m, '?')[:5] for m in stop['modes']])
        print(f"{stop['search_term']:<15} {stop['id']:<12} {stop['name'][:40]:<40} {modes_short}")
    
    print()
    
    # Output as JSON config
    print("=" * 80)
    print("JSON Configuration (for routes file)")
    print("=" * 80)
    print()
    
    stops_config = {
        "stops": [
            {
                "name": stop['search_term'],
                "id": stop['id'],
                "full_name": stop['name'],
                "modes": [TRANSPORT_MODES.get(m, f'Mode {m}') for m in stop['modes']]
            }
            for stop in found_stops
        ]
    }
    
    print(json.dumps(stops_config, indent=2))
    
    print()
    print("=" * 80)
    print("Example Routes Configuration")
    print("=" * 80)
    print()
    
    # Generate example routes between the found stops
    if len(found_stops) >= 2:
        example_routes = {
            "routes": []
        }
        
        # Create routes between consecutive stops
        for i in range(len(found_stops) - 1):
            origin = found_stops[i]
            dest = found_stops[i + 1]
            example_routes["routes"].append({
                "name": f"{origin['search_term']} to {dest['search_term']}",
                "origin": origin['id'],
                "destination": dest['id'],
                "origin_name": origin['name'],
                "destination_name": dest['name']
            })
        
        # Add a route from last to first for a round trip option
        if len(found_stops) > 2:
            origin = found_stops[-1]
            dest = found_stops[0]
            example_routes["routes"].append({
                "name": f"{origin['search_term']} to {dest['search_term']}",
                "origin": origin['id'],
                "destination": dest['id'],
                "origin_name": origin['name'],
                "destination_name": dest['name']
            })
        
        print(json.dumps(example_routes, indent=2))


if __name__ == '__main__':
    main()
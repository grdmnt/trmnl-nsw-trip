# TRMNL NSW Trip Planner Plugin

A local Go proxy service + LaraPaper recipe plugin for displaying NSW public transport departures on a TRMNL e-ink display.

## Architecture

```
LaraPaper Plugin (Recipe)     Go Proxy Service              TfNSW API
     |                                |                            |
     |-- polls every 5 min -------->|                            |
     |   GET /trips?origin=...        |                            |
     |                                |-- resolves stop names ---->|
     |                                |-- fetches trips --------->|
     |                                |                            |
     |<-- JSON with departures -------|                            |
     |                                |                            |
   [renders Blade template]                                    
```

## What It Does

- Shows the next 4 departures (train, bus, metro, light rail, ferry) from an origin to a destination
- Automatically resolves stop names to TfNSW stop IDs
- Uses real-time data when available, falls back to scheduled times
- Configurable origin and destination per plugin instance in LaraPaper UI

## Prerequisites

- [Go](https://go.dev/dl/) 1.22+
- A running [LaraPaper](https://github.com/usetrmnl/larapaper) instance
- A free [TfNSW Open Data API key](https://opendata.transport.nsw.gov.au/)

## 1. Build and Run the Go Proxy

### Option A: Native Build

```bash
cd trmnl-nsw-trip/api

# Build binary
make build

# Or run directly
make run

# Run with your API key
TFNSW_API_KEY="your-api-key-here" ./nsw-trip-api

# Or with custom port
TFNSW_API_KEY="your-key" PORT=9000 ./nsw-trip-api
```

### Option B: Docker

```bash
cd trmnl-nsw-trip/api

# Build Docker image
make docker
# Or manually:
docker build -t nsw-trip-api .

# Run container
make docker-run
# Or manually:
docker run --rm -p 8080:8080 -e TFNSW_API_KEY="your-api-key-here" nsw-trip-api

# Run with custom port
docker run --rm -p 9000:9000 -e PORT=9000 -e TFNSW_API_KEY="your-key" nsw-trip-api
```

The Docker image is built from a minimal `scratch` base image (no shell, ~15MB total).

The service starts on `http://localhost:8080` by default.

### Endpoints

- `GET /health` — Service status check
- `GET /trips?origin=West+Ryde&destination=Town+Hall` — Returns next 4 departures

### Example Response

```json
{
  "origin": "West Ryde",
  "destination": "Town Hall",
  "updated_at": "7:42am",
  "departures": [
    {
      "mode": "train",
      "dep": "7:45am",
      "arr": "8:02am",
      "duration": 17,
      "line": "T9",
      "platform": "3"
    },
    {
      "mode": "bus",
      "dep": "7:50am",
      "arr": "8:25am",
      "duration": 35,
      "line": "501"
    }
  ]
}
```

## 2. Install the LaraPaper Plugin

### Option A: Run the Seeder

Copy the seeder and view files into your LaraPaper project, then run:

```bash
cp larapaper/NswTripPlannerSeeder.php /path/to/larapaper/database/seeders/
cp larapaper/views/nsw-trip.blade.php /path/to/larapaper/resources/views/recipes/
```

Then in LaraPaper:
```bash
cd /path/to/larapaper
php artisan db:seed --class=NswTripPlannerSeeder
```

### Option B: Manual Plugin Creation

1. In LaraPaper web UI, go to **Plugins**
2. Create a new **Recipe** plugin
3. Set:
   - **Name:** `NSW Trip Planner`
   - **Data Strategy:** `polling`
   - **Polling URL:** `http://localhost:8080/trips?origin={{ origin_name | url_encode }}&destination={{ destination_name | url_encode }}`
   - **Polling Verb:** `GET`
   - **Refresh Interval:** `5` minutes
   - **Render Markup View:** `recipes.nsw-trip`
   - **Custom Fields:**
     - `origin_name` (text, default: `West Ryde`)
     - `destination_name` (text, default: `Town Hall`)
4. Save and add to a playlist

## 3. Configure Routes

The plugin supports multiple route instances via LaraPaper's duplicate feature:

1. In LaraPaper, go to **Plugins**
2. Find your `NSW Trip Planner` plugin
3. Click **Duplicate**
4. Edit the duplicated plugin's configuration:
   - Change `origin_name` and `destination_name`
5. Add all instances to a playlist

**Suggested routes:**
- West Ryde → Town Hall
- West Ryde → Ashfield
- West Ryde → Parramatta
- Town Hall → West Ryde

### Option C: Docker Compose

Create a `docker-compose.yml`:

```yaml
services:
  nsw-trip-api:
    build: ./api
    ports:
      - "8080:8080"
    environment:
      - TFNSW_API_KEY=${TFNSW_API_KEY}
      - PORT=8080
    restart: unless-stopped
```

Then run:

```bash
docker compose up -d
```

## 4. Running in Production

For a persistent background service, use systemd, Docker, or a process manager.

### Systemd Example

Create `/etc/systemd/system/nsw-trip-api.service`:

```ini
[Unit]
Description=NSW Trip Planner API Proxy
After=network.target

[Service]
Type=simple
User=trmnl
WorkingDirectory=/opt/trmnl-nsw-trip/api
ExecStart=/opt/trmnl-nsw-trip/api/nsw-trip-api
Environment="TFNSW_API_KEY=your-key-here"
Environment="PORT=8080"
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable nsw-trip-api
sudo systemctl start nsw-trip-api
```

## Troubleshooting

### "failed to resolve origin" error
- Check the Go service logs
- Verify the stop name is correct (try searching on TfNSW Trip Planner website)

### No departures showing
- Check that the NSW API key is valid and has quota remaining
- The Go service caches stop IDs for 24 hours — try restarting if a stop name has changed

### LaraPaper can't reach the Go service
- Ensure both are on the same network
- If LaraPaper is in Docker, use the host machine's IP instead of `localhost`
- Or run the Go service in the same Docker network

## Transport Mode Icons

The template uses single-letter mode indicators:
- **T** = Train
- **B** = Bus
- **M** = Metro
- **L** = Light Rail
- **F** = Ferry

## Notes

- Stop name resolution is cached for 24 hours to reduce API calls
- The NSW API returns data in UTC; the Go service converts to Sydney local time
- Platform information is only available when provided by the NSW API

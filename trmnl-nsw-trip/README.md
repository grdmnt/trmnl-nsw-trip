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

### Prerequisites

- LaraPaper running and accessible (local or Docker)
- The Go proxy service running and reachable from LaraPaper
- If LaraPaper is in Docker and the proxy is on the host, use the host's IP instead of `localhost`

### Step 1: Copy the Blade View Template

The Blade template renders the e-ink display layout. Copy it to LaraPaper's recipe views directory:

```bash
# If LaraPaper is local
cp larapaper/views/nsw-trip.blade.php /path/to/larapaper/resources/views/recipes/

# If LaraPaper is in Docker, copy into the container
docker cp larapaper/views/nsw-trip.blade.php larapaper-container:/var/www/html/resources/views/recipes/
```

### Step 2: Create the Plugin (Choose One Method)

#### Method A: Using the Seeder (Recommended)

Copy the seeder file into your LaraPaper project:

```bash
cp larapaper/NswTripPlannerSeeder.php /path/to/larapaper/database/seeders/
```

Then run the seeder to create the plugin automatically:

```bash
cd /path/to/larapaper
php artisan db:seed --class=NswTripPlannerSeeder
```

The seeder creates a plugin with these defaults:
- **Name:** `NSW Trip Planner`
- **Origin:** `West Ryde`
- **Destination:** `Town Hall`
- **Refresh:** Every 5 minutes

#### Method B: Manual Creation via Web UI

If you prefer to create it manually or customize settings:

1. **Open LaraPaper** in your browser (e.g., `http://localhost:4567`)
2. Go to **Plugins** in the left sidebar
3. Click **New Plugin** (or the `+` button)
4. Fill in the form:

| Field | Value |
|-------|-------|
| **Name** | `NSW Trip Planner` |
| **Strategy** | `polling` |
| **Polling URL** | `http://localhost:8080/trips?origin={{origin_name}}&destination={{destination_name}}` |
| **Polling Verb** | `GET` |
| **Refresh Interval** | `5` minutes |
| **Render Markup View** | `recipes.nsw-trip` |
| **Icon** | `train-front` |

5. Under **Custom Fields**, add two fields:

   **Field 1 - Origin:**
   - Key: `origin_name`
   - Type: `text`
   - Name: `Origin Stop Name`
   - Default: `West Ryde`

   **Field 2 - Destination:**
   - Key: `destination_name`
   - Type: `text`
   - Name: `Destination Stop Name`
   - Default: `Town Hall`

6. Click **Save**

### Step 3: Configure the Plugin

1. Find your new `NSW Trip Planner` plugin in the list
2. Click **Edit Configuration**
3. Set your origin and destination stop names:
   - `origin_name`: e.g., `West Ryde`, `Town Hall`, `Central`, `Parramatta`
   - `destination_name`: e.g., `Town Hall`, `Ashfield`, `Bondi Junction`
4. Click **Save**

> **Tip:** Use the exact stop names from the TfNSW Trip Planner website. The Go proxy resolves these automatically.

### Step 4: Test the Plugin

1. On the plugin detail page, click **Refresh Data** (or wait for the next polling cycle)
2. The plugin should fetch data from your Go proxy
3. Click **Preview** to see the rendered e-ink display
4. You should see a table with 4 departures showing Dep, Mode, Line, Arr, Dur, and Platform

### Step 5: Add to a Playlist

1. Go to **Playlists** in LaraPaper
2. Create or edit a playlist
3. Add your `NSW Trip Planner` plugin to the playlist
4. Set the display duration (e.g., 30 seconds)
5. Assign the playlist to your TRMNL device

## 3. Multiple Routes (Up to 4)

LaraPaper supports multiple plugin instances. Create additional routes by duplicating the plugin:

### How to Duplicate

1. Go to **Plugins**
2. Find your `NSW Trip Planner` plugin
3. Click the **Duplicate** button (or **... → Duplicate**)
4. Give it a new name, e.g., `NSW Trip - Town Hall to West Ryde`
5. Edit the configuration with new origin/destination values
6. Save and add to your playlist

### Suggested Route Combinations

| Route Name | Origin | Destination |
|------------|--------|-------------|
| Morning Commute | West Ryde | Town Hall |
| Evening Return | Town Hall | West Ryde |
| Weekend Shopping | West Ryde | Top Ryde |
| Alternative Route | West Ryde | Ashfield |

**Note:** Each duplicate instance polls the Go proxy independently. If you have 4 instances with 5-minute refresh, that's 4 API calls every 5 minutes — well within TfNSW free tier limits.

## 4. Troubleshooting LaraPaper Setup

### "View not found" error
- Make sure `nsw-trip.blade.php` is in `resources/views/recipes/`
- The view path should be exactly `recipes.nsw-trip` in the plugin settings

### "Failed to fetch data" error
- Check that the Go proxy is running: `curl http://localhost:8080/health`
- If LaraPaper is in Docker, replace `localhost` with the host IP or container name
- Verify the API key is set correctly in the proxy

### Empty display / no departures
- Check the plugin's **Data Payload** tab to see the raw JSON
- Verify the stop names are correct (try them on the TfNSW website first)
- Check the Go proxy logs for errors

### Plugin shows old data
- The refresh interval is 5 minutes by default
- You can manually refresh from the plugin detail page
- Check that LaraPaper's queue worker is running (for scheduled refreshes)

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

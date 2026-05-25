package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"net/url"
	"os"
	"regexp"
	"sort"
	"strings"
	"sync"
	"time"

	_ "time/tzdata"
)

const (
	nswAPIBase = "https://api.transport.nsw.gov.au/v1/tp"
	cacheTTL   = 24 * time.Hour
)

var (
	apiKey     string
	stopCache  = make(map[string]*cachedStop)
	cacheMutex sync.RWMutex
)

type cachedStop struct {
	id        string
	name      string
	expiresAt time.Time
}

type StopFinderResponse struct {
	Locations []struct {
		ID              string `json:"id"`
		Name            string `json:"name"`
		DisassembledName string `json:"disassembledName"`
		Type            string `json:"type"`
		IsBest          bool   `json:"isBest"`
		MatchQuality    int    `json:"matchQuality"`
	} `json:"locations"`
}

type TripResponse struct {
	Journeys []struct {
		Legs []struct {
			Origin struct {
				DepartureTimePlanned   string `json:"departureTimePlanned"`
				DepartureTimeEstimated string `json:"departureTimeEstimated"`
				Name                   string `json:"name"`
				DisassembledName       string `json:"disassembledName"`
			} `json:"origin"`
			Destination struct {
				ArrivalTimePlanned   string `json:"arrivalTimePlanned"`
				ArrivalTimeEstimated string `json:"arrivalTimeEstimated"`
				Name                 string `json:"name"`
				DisassembledName     string `json:"disassembledName"`
			} `json:"destination"`
			Transportation struct {
				Number      string `json:"number"`
				Name        string `json:"name"`
				Destination struct {
					Name string `json:"name"`
				} `json:"destination"`
				Product struct {
					Class int    `json:"class"`
					Name  string `json:"name"`
				} `json:"product"`
			} `json:"transportation"`
			Duration int `json:"duration"`
			Distance int `json:"distance"`
		} `json:"legs"`
	} `json:"journeys"`
}

type Departure struct {
	Mode     string `json:"mode"`
	Dep      string `json:"dep"`
	Arr      string `json:"arr"`
	Duration int    `json:"duration"`
	Line     string `json:"line"`
	Platform string `json:"platform,omitempty"`
}

type TripsResponse struct {
	Origin      string      `json:"origin"`
	Destination string      `json:"destination"`
	UpdatedAt   string      `json:"updated_at"`
	Departures  []Departure `json:"departures"`
}

func main() {
	apiKey = os.Getenv("TFNSW_API_KEY")
	if apiKey == "" {
		log.Fatal("TFNSW_API_KEY environment variable is required")
	}

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	http.HandleFunc("/health", healthHandler)
	http.HandleFunc("/trips", tripsHandler)

	log.Printf("NSW Trip API starting on port %s", port)
	log.Fatal(http.ListenAndServe(":"+port, nil))
}

func healthHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
}

func tripsHandler(w http.ResponseWriter, r *http.Request) {
	origin := strings.TrimSpace(r.URL.Query().Get("origin"))
	destination := strings.TrimSpace(r.URL.Query().Get("destination"))

	if origin == "" || destination == "" {
		http.Error(w, `{"error":"origin and destination query params are required"}`, http.StatusBadRequest)
		return
	}

	originID, originName, err := resolveStop(origin)
	if err != nil {
		log.Printf("Failed to resolve origin '%s': %v", origin, err)
		http.Error(w, fmt.Sprintf(`{"error":"failed to resolve origin: %s"}`, err), http.StatusBadRequest)
		return
	}

	destID, destName, err := resolveStop(destination)
	if err != nil {
		log.Printf("Failed to resolve destination '%s': %v", destination, err)
		http.Error(w, fmt.Sprintf(`{"error":"failed to resolve destination: %s"}`, err), http.StatusBadRequest)
		return
	}

	departures, err := fetchTrips(originID, destID)
	if err != nil {
		log.Printf("Failed to fetch trips: %v", err)
		http.Error(w, fmt.Sprintf(`{"error":"failed to fetch trips: %s"}`, err), http.StatusInternalServerError)
		return
	}

	now := sydneyNow()

	resp := TripsResponse{
		Origin:      originName,
		Destination: destName,
		UpdatedAt:   now.Format("3:04pm"),
		Departures:  departures,
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(resp)
}

func resolveStop(query string) (string, string, error) {
	cacheMutex.RLock()
	if cached, ok := stopCache[strings.ToLower(query)]; ok && cached.expiresAt.After(time.Now()) {
		cacheMutex.RUnlock()
		return cached.id, cached.name, nil
	}
	cacheMutex.RUnlock()

	u, _ := url.Parse(nswAPIBase + "/stop_finder")
	q := u.Query()
	q.Set("outputFormat", "rapidJSON")
	q.Set("type_sf", "any")
	q.Set("name_sf", query)
	q.Set("coordOutputFormat", "EPSG:4326")
	q.Set("TfNSWSF", "true")
	q.Set("anyMaxSizeHitList", "10")
	u.RawQuery = q.Encode()

	req, err := http.NewRequest("GET", u.String(), nil)
	if err != nil {
		return "", "", err
	}
	req.Header.Set("Authorization", "apikey "+apiKey)
	req.Header.Set("Accept", "application/json")

	client := &http.Client{Timeout: 15 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return "", "", err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", "", fmt.Errorf("stop_finder returned %d", resp.StatusCode)
	}

	var result StopFinderResponse
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return "", "", err
	}

	if len(result.Locations) == 0 {
		return "", "", fmt.Errorf("no stops found for '%s'", query)
	}

	// Find best match
	var best *struct {
		ID              string `json:"id"`
		Name            string `json:"name"`
		DisassembledName string `json:"disassembledName"`
		Type            string `json:"type"`
		IsBest          bool   `json:"isBest"`
		MatchQuality    int    `json:"matchQuality"`
	}
	for i := range result.Locations {
		if result.Locations[i].IsBest {
			best = &result.Locations[i]
			break
		}
	}
	if best == nil {
		for i := range result.Locations {
			if result.Locations[i].Type == "stop" {
				best = &result.Locations[i]
				break
			}
		}
	}
	if best == nil {
		best = &result.Locations[0]
	}

	name := best.DisassembledName
	if name == "" {
		name = best.Name
	}
	// Clean up name
	name = strings.TrimSpace(strings.ReplaceAll(name, " Station", ""))

	cacheMutex.Lock()
	stopCache[strings.ToLower(query)] = &cachedStop{
		id:        best.ID,
		name:      name,
		expiresAt: time.Now().Add(cacheTTL),
	}
	cacheMutex.Unlock()

	return best.ID, name, nil
}

func sydneyNow() time.Time {
	loc, err := time.LoadLocation("Australia/Sydney")
	if err != nil {
		log.Printf("Warning: could not load Sydney timezone, using UTC: %v", err)
		return time.Now().UTC()
	}
	return time.Now().In(loc)
}

func fetchTrips(originID, destID string) ([]Departure, error) {
	now := sydneyNow()

	u, _ := url.Parse(nswAPIBase + "/trip")
	q := u.Query()
	q.Set("outputFormat", "rapidJSON")
	q.Set("coordOutputFormat", "EPSG:4326")
	q.Set("depArrMacro", "dep")
	q.Set("itdDate", now.Format("20060102"))
	q.Set("itdTime", now.Format("1504"))
	q.Set("type_origin", "stop")
	q.Set("name_origin", originID)
	q.Set("type_destination", "stop")
	q.Set("name_destination", destID)
	q.Set("calcNumberOfTrips", "15")
	q.Set("TfNSWTR", "true")
	u.RawQuery = q.Encode()

	req, err := http.NewRequest("GET", u.String(), nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Authorization", "apikey "+apiKey)
	req.Header.Set("Accept", "application/json")

	client := &http.Client{Timeout: 20 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("trip returned %d", resp.StatusCode)
	}

	var result TripResponse
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil, err
	}

	return extractDepartures(result)
}

var trainLineRegex = regexp.MustCompile(`^(T\d+).*`)

func cleanLine(line string, mode string) string {
	line = strings.TrimSpace(line)
	if mode == "train" || mode == "metro" {
		if match := trainLineRegex.FindStringSubmatch(line); len(match) > 1 {
			return match[1]
		}
	}
	return line
}

func extractDepartures(result TripResponse) ([]Departure, error) {
	now := sydneyNow()
	cutoff := now.Add(-2 * time.Minute)

	var departures []Departure
	seen := make(map[string]bool)

	for _, journey := range result.Journeys {
		if len(journey.Legs) == 0 {
			continue
		}

		// Find first non-walking leg for mode/line info, but use first leg's origin for departure time
		firstLeg := journey.Legs[0]
		lastLeg := journey.Legs[len(journey.Legs)-1]

		var transportLeg *struct {
			Origin struct {
				DepartureTimePlanned   string `json:"departureTimePlanned"`
				DepartureTimeEstimated string `json:"departureTimeEstimated"`
				Name                   string `json:"name"`
				DisassembledName       string `json:"disassembledName"`
			} `json:"origin"`
			Destination struct {
				ArrivalTimePlanned   string `json:"arrivalTimePlanned"`
				ArrivalTimeEstimated string `json:"arrivalTimeEstimated"`
				Name                 string `json:"name"`
				DisassembledName     string `json:"disassembledName"`
			} `json:"destination"`
			Transportation struct {
				Number      string `json:"number"`
				Name        string `json:"name"`
				Destination struct {
					Name string `json:"name"`
				} `json:"destination"`
				Product struct {
					Class int    `json:"class"`
					Name  string `json:"name"`
				} `json:"product"`
			} `json:"transportation"`
			Duration int `json:"duration"`
			Distance int `json:"distance"`
		}
		for i := range journey.Legs {
			pc := journey.Legs[i].Transportation.Product.Class
			if pc != 99 && pc != 100 && pc != 0 {
				transportLeg = &journey.Legs[i]
				break
			}
		}
		if transportLeg == nil {
			transportLeg = &firstLeg
		}

		// Get departure time - prefer estimated, fall back to planned
		depStr := firstLeg.Origin.DepartureTimeEstimated
		if depStr == "" {
			depStr = firstLeg.Origin.DepartureTimePlanned
		}
		arrStr := lastLeg.Destination.ArrivalTimeEstimated
		if arrStr == "" {
			arrStr = lastLeg.Destination.ArrivalTimePlanned
		}

		if depStr == "" {
			continue
		}

		depTime, err := parseAPITime(depStr)
		if err != nil {
			continue
		}
		depTime = depTime.In(now.Location())

		// Skip departures more than 2 minutes in the past
		if depTime.Before(cutoff) {
			continue
		}

		// Determine mode from first transport leg
		mode := "other"
		productClass := transportLeg.Transportation.Product.Class
		switch productClass {
		case 1:
			mode = "train"
		case 5:
			mode = "bus"
		case 2:
			mode = "metro"
		case 4:
			mode = "light_rail"
		case 9:
			mode = "ferry"
		}

		// Line number
		line := transportLeg.Transportation.Number
		if line == "" {
			line = transportLeg.Transportation.Name
		}
		line = cleanLine(line, mode)

		// Duration - use total journey duration
		duration := 0
		for _, leg := range journey.Legs {
			duration += leg.Duration
		}
		duration = duration / 60 // seconds to minutes

		// Calculate duration from times if available
		arrTime, _ := parseAPITime(arrStr)
		if arrTime != nil {
			arrTime = arrTime.In(now.Location())
			duration = int(arrTime.Sub(depTime).Minutes())
		}

		// Platform - try to extract from transport leg origin name
		platform := ""
		originName := transportLeg.Origin.DisassembledName
		if originName == "" {
			originName = transportLeg.Origin.Name
		}
		if idx := strings.Index(originName, "Platform "); idx != -1 {
			rest := originName[idx+9:]
			if spaceIdx := strings.Index(rest, ","); spaceIdx != -1 {
				platform = rest[:spaceIdx]
			} else {
				platform = rest
			}
		}

		// Deduplicate by departure time + line
		key := depTime.Format("15:04") + "-" + line
		if seen[key] {
			continue
		}
		seen[key] = true

		departures = append(departures, Departure{
			Mode:     mode,
			Dep:      depTime.Format("3:04pm"),
			Arr:      "",
			Duration: duration,
			Line:     line,
			Platform: platform,
		})

		if arrTime != nil {
			departures[len(departures)-1].Arr = arrTime.Format("3:04pm")
		}
	}

	// Sort by departure time
	sort.Slice(departures, func(i, j int) bool {
		return extractTimeForSort(departures[i].Dep) < extractTimeForSort(departures[j].Dep)
	})

	// Limit to next 4
	if len(departures) > 4 {
		departures = departures[:4]
	}

	return departures, nil
}

func parseAPITime(t string) (time.Time, error) {
	// API format: 2026-05-25T07:45:00Z or with timezone
	if strings.HasSuffix(t, "Z") {
		t = t[:len(t)-1] + "+00:00"
	}
	return time.Parse(time.RFC3339, t)
}

func extractTimeForSort(t string) int {
	// t is in format "3:04pm" or "10:04pm"
	var h, m int
	var suffix string
	fmt.Sscanf(t, "%d:%d%s", &h, &m, &suffix)
	if suffix == "pm" && h != 12 {
		h += 12
	}
	if suffix == "am" && h == 12 {
		h = 0
	}
	return h*60 + m
}

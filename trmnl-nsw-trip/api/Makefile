.PHONY: build run clean docker docker-run

build:
	go build -o nsw-trip-api main.go

run:
	go run main.go

clean:
	rm -f nsw-trip-api

docker:
	docker build -t nsw-trip-api .

docker-run:
	docker run --rm -p 8080:8080 -e TFNSW_API_KEY=$(TFNSW_API_KEY) nsw-trip-api

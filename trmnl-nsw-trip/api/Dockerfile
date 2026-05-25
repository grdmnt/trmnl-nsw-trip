# Build stage
FROM golang:1.22-alpine AS builder

WORKDIR /app

# Install ca-certificates for HTTPS requests to TfNSW API
RUN apk add --no-cache ca-certificates git

# Copy go module files
COPY go.mod ./
RUN go mod download

# Copy source code
COPY main.go ./

# Build the binary
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o nsw-trip-api main.go

# Final stage
FROM scratch

WORKDIR /

# Copy CA certificates from builder (needed for HTTPS to TfNSW API)
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/

# Copy the binary from builder
COPY --from=builder /app/nsw-trip-api /nsw-trip-api

# Expose the default port
EXPOSE 8080

# Environment variables
ENV PORT=8080
ENV TFNSW_API_KEY=""

# Run the binary
ENTRYPOINT ["/nsw-trip-api"]

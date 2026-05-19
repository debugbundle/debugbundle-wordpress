#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
COMPOSE_FILE="$REPO_DIR/docker-compose.smoke.yml"
PROJECT_NAME="debugbundle-wordpress-smoke"
WP_URL="http://127.0.0.1:18080"
RELAY_URL="$WP_URL/?rest_route=/debugbundle/v1/browser"

compose() {
  docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" "$@"
}

cleanup() {
  compose down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

compose up -d --wait db wordpress

compose run --rm wpcli wp core install \
  --allow-root \
  --url="$WP_URL" \
  --title="DebugBundle Smoke" \
  --admin_user="admin" \
  --admin_password="password" \
  --admin_email="admin@example.com" \
  --skip-email

compose run --rm wpcli wp plugin activate debugbundle --allow-root

response_file=$(mktemp)
status_code=$(curl -sS -o "$response_file" -w "%{http_code}" \
  -X POST "$RELAY_URL" \
  -H "Origin: $WP_URL" \
  -H "Content-Type: application/json" \
  --data '{}')

if [ "$status_code" != "404" ]; then
  echo "Expected relay route to respond with 404 while frontend capture is disabled, got $status_code" >&2
  cat "$response_file" >&2
  exit 1
fi

if ! grep -q 'frontend_capture_disabled' "$response_file"; then
  echo "Expected relay route response to mention frontend_capture_disabled" >&2
  cat "$response_file" >&2
  exit 1
fi

rm -f "$response_file"

echo "WordPress smoke passed: plugin activated and relay route responded as expected."
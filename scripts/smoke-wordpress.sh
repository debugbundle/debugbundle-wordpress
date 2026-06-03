#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
COMPOSE_FILE="$REPO_DIR/docker-compose.smoke.yml"
PROJECT_NAME="debugbundle-wordpress-smoke"
WP_URL="http://127.0.0.1:18080"
RELAY_URL="$WP_URL/?rest_route=/debugbundle/v1/browser"
MOCK_EVENTS_FILE="$REPO_DIR/.smoke/ingestion-events.ndjson"

compose() {
  docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" "$@"
}

prepare_plugin() {
  if [ ! -r "$REPO_DIR/vendor/autoload.php" ]; then
    docker run --rm -t \
      -v "$REPO_DIR:/workspace" \
      -w /workspace \
      composer:2 \
      composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  fi

  if [ ! -r "$REPO_DIR/assets/dist/debugbundle-browser.js" ]; then
    docker run --rm -t \
      -v "$REPO_DIR:/workspace" \
      -w /workspace \
      node:24-alpine \
      sh -lc "corepack enable && corepack pnpm install --frozen-lockfile=false && corepack pnpm build"
  fi
}

cleanup() {
  compose down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

rm -rf "$REPO_DIR/.smoke"
mkdir -p "$REPO_DIR/.smoke"

prepare_plugin

compose up -d --wait db mock-ingestion wordpress

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

settings_json='{"enabled":true,"project_token":"dbundle_proj_smoke","environment":"development","service":"wordpress-smoke","endpoint":"http://mock-ingestion:18081/v1/events","backend_capture_enabled":true,"frontend_capture_enabled":true,"sample_rate":1,"browser_session_sample_rate":1,"browser_max_events_per_session":100,"browser_capture_console":false,"log_level":"warning","delete_on_uninstall":false,"settings_version":1}'
compose run --rm wpcli wp option update debugbundle_settings "$settings_json" --format=json --allow-root

compose run --rm wpcli wp eval '
$result = (new \DebugBundleWp\AdminTestEvents(new \DebugBundleWp\Settings()))->sendBackend();
if (!$result->success) {
    fwrite(STDERR, $result->message . PHP_EOL);
    exit(1);
}
' --allow-root

compose run --rm wpcli wp eval '
$result = (new \DebugBundleWp\AdminTestEvents(new \DebugBundleWp\Settings()))->sendFrontend();
if (!$result->success) {
    fwrite(STDERR, $result->message . PHP_EOL);
    exit(1);
}
' --allow-root

browser_payload='{"batch":[{"schema_version":"2026-03-01","event_id":"00000000-0000-4000-8000-000000000001","event_type":"frontend_exception","occurred_at":"2026-05-19T00:00:00Z","sdk_name":"@debugbundle/sdk-browser","sdk_version":"1.0.1","service":{"name":"wordpress-smoke-browser","environment":"development"},"correlation":{"trace_id":"00000000-0000-4000-8000-000000000002"},"payload":{"name":"DebugBundleWordPressSmokeFrontendError","message":"DebugBundle WordPress smoke frontend event","stack":"DebugBundleWordPressSmokeFrontendError: DebugBundle WordPress smoke frontend event","url":"http://127.0.0.1:18080/","breadcrumbs":[]}}]}'
response_file=$(mktemp)
status_code=$(curl -sS -o "$response_file" -w "%{http_code}" \
  -X POST "$RELAY_URL" \
  -H "Origin: $WP_URL" \
  -H "Content-Type: application/json" \
  --data "$browser_payload")

if [ "$status_code" != "202" ]; then
  echo "Expected configured relay route to accept frontend event, got $status_code" >&2
  cat "$response_file" >&2
  exit 1
fi

rm -f "$response_file"

if ! grep -q '"event_type":"backend_exception"' "$MOCK_EVENTS_FILE"; then
  echo "Expected mock ingestion to receive a backend_exception event" >&2
  cat "$MOCK_EVENTS_FILE" >&2
  exit 1
fi

if ! grep -q '"event_type":"frontend_exception"' "$MOCK_EVENTS_FILE"; then
  echo "Expected mock ingestion to receive a frontend_exception event" >&2
  cat "$MOCK_EVENTS_FILE" >&2
  exit 1
fi

if ! grep -q 'Bearer dbundle_proj_smoke' "$MOCK_EVENTS_FILE"; then
  echo "Expected mock ingestion to receive the server-side project token" >&2
  cat "$MOCK_EVENTS_FILE" >&2
  exit 1
fi

touch "$REPO_DIR/.smoke/fail-ingestion"
spool_payload='{"batch":[{"schema_version":"2026-03-01","event_id":"00000000-0000-4000-8000-000000000101","event_type":"frontend_exception","occurred_at":"2026-05-19T00:00:00Z","sdk_name":"@debugbundle/sdk-browser","sdk_version":"1.0.1","service":{"name":"wordpress-smoke-browser","environment":"development"},"correlation":{"trace_id":"00000000-0000-4000-8000-000000000102"},"payload":{"name":"DebugBundleWordPressSpoolSmokeError","message":"DebugBundle WordPress spool smoke event","stack":"DebugBundleWordPressSpoolSmokeError: DebugBundle WordPress spool smoke event","url":"http://127.0.0.1:18080/","breadcrumbs":[]}}]}'
response_file=$(mktemp)
status_code=$(curl -sS -o "$response_file" -w "%{http_code}" \
  -X POST "$RELAY_URL" \
  -H "Origin: $WP_URL" \
  -H "Content-Type: application/json" \
  --data "$spool_payload")

if [ "$status_code" != "202" ]; then
  echo "Expected relay route to accept and spool event during mock ingestion outage, got $status_code" >&2
  cat "$response_file" >&2
  exit 1
fi

rm -f "$response_file"

compose run --rm wpcli wp eval '
$stats = (new \DebugBundleWp\RelaySpool())->stats();
if ($stats["count"] < 1) {
    fwrite(STDERR, "Expected at least one relay spool file during mock outage" . PHP_EOL);
    exit(1);
}
' --allow-root

rm -f "$REPO_DIR/.smoke/fail-ingestion"

compose run --rm wpcli wp eval '
(new \DebugBundleWp\BrowserRelayRoute(new \DebugBundleWp\Settings(), "debugbundle_flush_relay_spool"))->flushSpool();
$stats = (new \DebugBundleWp\RelaySpool())->stats();
if ($stats["count"] !== 0) {
    fwrite(STDERR, "Expected relay spool to be empty after flush" . PHP_EOL);
    exit(1);
}
' --allow-root

if ! grep -q '00000000-0000-4000-8000-000000000101' "$MOCK_EVENTS_FILE"; then
  echo "Expected mock ingestion to receive the flushed spooled frontend event" >&2
  cat "$MOCK_EVENTS_FILE" >&2
  exit 1
fi

echo "WordPress smoke passed: plugin activated, admin tests sent, relay events reached mock ingestion, and spool retry flushed."

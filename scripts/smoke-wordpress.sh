#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
COMPOSE_FILE="$REPO_DIR/docker-compose.smoke.yml"
PROJECT_NAME="debugbundle-wordpress-smoke"
WP_URL="http://127.0.0.1:18080"
RELAY_URL="$WP_URL/?rest_route=/debugbundle/v1/browser"
MOCK_EVENTS_FILE="$REPO_DIR/.smoke/ingestion-events.ndjson"
PLUGIN_STAGE_DIR="$REPO_DIR/.smoke/plugin"
PHP_SDK_DIR=${DEBUGBUNDLE_PHP_SDK_CHECKOUT:-}
USE_ASSEMBLED_ARTIFACT=${DEBUGBUNDLE_USE_ASSEMBLED_ARTIFACT:-0}
VERSION=${VERSION:-1.5.0}

compose() {
  docker compose -p "$PROJECT_NAME" -f "$COMPOSE_FILE" "$@"
}

prepare_plugin() {
  if [ "$USE_ASSEMBLED_ARTIFACT" = "1" ]; then
    if [ -n "$PHP_SDK_DIR" ]; then
      echo "An assembled release smoke cannot use a PHP source overlay" >&2
      exit 1
    fi
    if [ ! -r "$REPO_DIR/.dist/debugbundle-wordpress-${VERSION}.zip" ] ||
      [ ! -r "$REPO_DIR/.dist/debugbundle-wordpress-${VERSION}.zip.sha256" ]; then
      echo "The assembled WordPress release ZIP and checksum are required" >&2
      exit 1
    fi
    (cd "$REPO_DIR/.dist" && sha256sum -c "debugbundle-wordpress-${VERSION}.zip.sha256")
  else
  if [ -n "$PHP_SDK_DIR" ] && [ ! -r "$PHP_SDK_DIR/composer.json" ]; then
    echo "The coordinated PHP SDK checkout is required at $PHP_SDK_DIR" >&2
    exit 1
  fi

  if [ ! -r "$REPO_DIR/assets/dist/debugbundle-browser.js" ] || [ ! -r "$REPO_DIR/assets/dist/sdk-build.json" ]; then
    docker run --rm -t \
      -v "$REPO_DIR:/workspace" \
      -w /workspace \
      node:24-alpine \
      sh -lc "corepack enable && corepack pnpm install --frozen-lockfile=false && corepack pnpm build"
  fi

  if [ -n "$PHP_SDK_DIR" ]; then
    docker run --rm -t \
      -v "$REPO_DIR:/workspace" \
      -v "$PHP_SDK_DIR:/sdk-php:ro" \
      -w /workspace \
      -e DEBUGBUNDLE_PHP_SDK_SOURCE=/sdk-php \
      -e DEBUGBUNDLE_SOURCE_OVERLAY_SMOKE=1 \
      composer:2 \
      ./scripts/assemble-release.sh "$VERSION"
  else
    docker run --rm -t \
      -v "$REPO_DIR:/workspace" \
      -w /workspace \
      composer:2 \
      ./scripts/assemble-release.sh "$VERSION"
  fi
  fi

  mkdir -p "$PLUGIN_STAGE_DIR"
  docker run --rm -t \
    -v "$REPO_DIR:/workspace" \
    -w /workspace \
    composer:2 \
    unzip -q ".dist/debugbundle-wordpress-${VERSION}.zip" -d .smoke/plugin

  if [ ! -r "$PLUGIN_STAGE_DIR/debugbundle/vendor/debugbundle/sdk-php/src/BeforeSend.php" ]; then
    echo "Assembled WordPress ZIP does not contain the coordinated PHP beforeSend implementation" >&2
    exit 1
  fi
}

cleanup() {
  compose down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

rm -rf "$REPO_DIR/.smoke"
mkdir -p "$REPO_DIR/.smoke"

prepare_plugin
if [ -z "$PHP_SDK_DIR" ]; then
  # All staged and publication lanes execute the asset and PHP configuration
  # from this exact checksum-verified ZIP, with no checked-out source overlay.
  docker run --rm -v "$REPO_DIR:/workspace:ro" -w /workspace composer:2 \
    php scripts/browser-asset-config.php /workspace/.smoke/plugin/debugbundle \
    > "$REPO_DIR/.smoke/browser-inline.js"
  docker run --rm -v "$REPO_DIR:/workspace:ro" -w /workspace node:24-alpine \
    node scripts/smoke-browser-asset.mjs .smoke/plugin/debugbundle .smoke/browser-inline.js
else
  echo "Source-overlay diagnostic smoke: Browser 3 release qualification is not being certified."
fi
browser_sdk_version=$(python3 -c 'import json, sys; print(json.load(open(sys.argv[1]))["version"])' "$PLUGIN_STAGE_DIR/debugbundle/assets/dist/sdk-build.json")
DEBUGBUNDLE_WORDPRESS_PLUGIN_DIR="$PLUGIN_STAGE_DIR/debugbundle"
export DEBUGBUNDLE_WORDPRESS_PLUGIN_DIR

compose up -d --wait db mock-ingestion wordpress

compose run --rm wpcli wp core install \
  --allow-root \
  --url="$WP_URL" \
  --title="DebugBundle Smoke" \
  --admin_user="admin" \
  --admin_password="password" \
  --admin_email="admin@example.com" \
  --skip-email

wordpress_version=$(compose run --rm wpcli wp core version --allow-root)
if [ "$wordpress_version" != "7.1.1" ]; then
  echo "Expected WordPress 7.1.1 in the compatibility smoke, got $wordpress_version" >&2
  exit 1
fi

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
$bootstrap = new \DebugBundleWp\SdkBootstrap(new \DebugBundleWp\Settings());
$bootstrap->refreshConfig();
$cache = get_transient("debugbundle_sdk_capture_config");
if (!is_array($cache) || ($cache["payload"]["capture_policy"]["capture_logs"] ?? null) !== "error"
    || ($cache["token_hash"] ?? null) !== hash("sha256", "Bearer dbundle_proj_smoke")
    || str_contains(json_encode($cache), "dbundle_proj_smoke")) {
    fwrite(STDERR, "Packaged plugin did not authenticate and cache project-scoped remote policy" . PHP_EOL);
    exit(1);
}
$captured = [];
add_filter("debugbundle_before_send", static function (array $event) use (&$captured): ?array {
    if (($event["event_type"] ?? null) === "log_event") {
        $captured[] = $event["payload"]["message"] ?? "";
        return null;
    }
    return $event;
});
$bootstrap->register();
$sdk = (new ReflectionProperty($bootstrap, "sdk"))->getValue($bootstrap);
$sdk->captureLog("remote policy warning blocked", "warning");
$sdk->captureLog("remote policy error kept", "error");
if ($captured !== ["remote policy error kept"]) {
    fwrite(STDERR, "Packaged plugin did not apply authenticated remote policy" . PHP_EOL);
    exit(1);
}
$sdk->reset();
' --allow-root

compose run --rm wpcli wp eval '
$captured = [];
add_filter("debugbundle_before_send", static function (array $event) use (&$captured): ?array {
    $message = $event["payload"]["message"] ?? "";
    if (str_starts_with($message, "DebugBundle suppression smoke")) {
        $captured[] = $message;
        return null;
    }
    return $event;
});
(new \DebugBundleWp\SdkBootstrap(new \DebugBundleWp\Settings()))->register();
$previous = error_reporting(E_ALL);
@trigger_error("DebugBundle suppression smoke suppressed", E_USER_WARNING);
error_reporting(E_ALL & ~E_USER_NOTICE);
trigger_error("DebugBundle suppression smoke masked", E_USER_NOTICE);
error_reporting(E_ALL);
trigger_error("DebugBundle suppression smoke accepted", E_USER_WARNING);
error_reporting($previous);
if ($captured !== ["DebugBundle suppression smoke accepted"]) {
    fwrite(STDERR, "Packaged plugin did not preserve PHP error suppression" . PHP_EOL);
    exit(1);
}
' --allow-root

compose run --rm wpcli wp eval '
$GLOBALS["debugbundle_smoke_filter_message"] = "DebugBundle WordPress backend filtered smoke event";
add_filter("debugbundle_before_send", static function (array $event): array {
    if (($event["event_type"] ?? null) === "backend_exception") {
        $event["payload"]["message"] = $GLOBALS["debugbundle_smoke_filter_message"];
    }
    return $event;
});
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

browser_payload='{"batch":[{"schema_version":"2026-03-01","event_id":"00000000-0000-4000-8000-000000000001","event_type":"frontend_exception","occurred_at":"2026-05-19T00:00:00Z","sdk_name":"@debugbundle/sdk-browser","sdk_version":"2.0.0","service":{"name":"wordpress-smoke-browser","environment":"development"},"correlation":{"trace_id":"00000000-0000-4000-8000-000000000002"},"payload":{"name":"DebugBundleWordPressSmokeFrontendError","message":"DebugBundle WordPress smoke frontend event","stack":"DebugBundleWordPressSmokeFrontendError: DebugBundle WordPress smoke frontend event","url":"http://127.0.0.1:18080/","breadcrumbs":[]}}]}'
browser_payload=$(printf '%s' "$browser_payload" | python3 -c 'import json, sys; value=json.load(sys.stdin); value["batch"][0]["sdk_version"]=sys.argv[1]; print(json.dumps(value))' "$browser_sdk_version")
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

if ! grep -q 'DebugBundle WordPress backend filtered smoke event' "$MOCK_EVENTS_FILE"; then
  echo "Expected assembled ZIP backend beforeSend filter mutation to reach mock ingestion" >&2
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
# Keep a legacy Browser 2.x event as an explicit relay/spool compatibility case.
spool_payload='{"batch":[{"schema_version":"2026-03-01","event_id":"00000000-0000-4000-8000-000000000101","event_type":"frontend_exception","occurred_at":"2026-05-19T00:00:00Z","sdk_name":"@debugbundle/sdk-browser","sdk_version":"2.0.0","service":{"name":"wordpress-smoke-browser","environment":"development"},"correlation":{"trace_id":"00000000-0000-4000-8000-000000000102"},"payload":{"name":"DebugBundleWordPressSpoolSmokeError","message":"DebugBundle WordPress spool smoke event","stack":"DebugBundleWordPressSpoolSmokeError: DebugBundle WordPress spool smoke event","url":"http://127.0.0.1:18080/","breadcrumbs":[]}}]}'
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

echo "WordPress smoke passed: authenticated config policy, PHP suppression, admin and relay delivery, and spool retry."

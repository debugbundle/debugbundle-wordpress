#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
VERSION=${1:-${VERSION:-}}

if [ -z "$VERSION" ]; then
  echo "Usage: ./scripts/assemble-release.sh <version>" >&2
  exit 1
fi

case "$VERSION" in
  [0-9]*.[0-9]*.[0-9]*) ;;
  *)
    echo "Version must look like x.y.z" >&2
    exit 1
    ;;
esac

cd "$REPO_DIR"

require_grep() {
  pattern=$1
  file=$2
  message=$3

  if ! grep -Eq "$pattern" "$file"; then
    echo "$message" >&2
    exit 1
  fi
}

require_grep '^[[:space:]]*\* Version:[[:space:]]+'"$VERSION"'$' debugbundle.php "debugbundle.php version does not match $VERSION"
require_grep '^Stable tag: '"$VERSION"'$' readme.txt "readme.txt stable tag does not match $VERSION"
require_grep '^  "version": "'"$VERSION"'",$' package.json "package.json version does not match $VERSION"
require_grep '^## \['"$VERSION"'\] - ' CHANGELOG.md "CHANGELOG.md is missing a $VERSION heading"
if [ -f src/CachedConfigFetcher.php ]; then
  if [ -n "${DEBUGBUNDLE_PHP_SDK_SOURCE:-}" ]; then
    if [ "${DEBUGBUNDLE_SOURCE_OVERLAY_SMOKE:-}" != "1" ]; then
      echo "Source-overlay packaging is only permitted for an explicit local smoke." >&2
      exit 1
    fi
  else
    case "$VERSION" in
      2.*) ;;
      *) echo "Bounded request-end delivery requires a WordPress 2.x major release." >&2; exit 1 ;;
    esac
    require_grep '"debugbundle/sdk-php":[[:space:]]*"\^2\.0' composer.json "WordPress 2.x must require the PHP 2.x SDK"
    require_grep '"version":[[:space:]]*"v?2\.' composer.lock "WordPress 2.x must lock the published PHP 2.x SDK"
  fi
fi
if [ -f .debugbundle-staged-candidate.json ]; then
  if [ "${DEBUGBUNDLE_STAGED_ARTIFACT_CHECK:-}" != "1" ]; then
    echo "A staged candidate cannot be assembled as a public release." >&2
    exit 1
  fi
elif [ "${DEBUGBUNDLE_SOURCE_OVERLAY_SMOKE:-}" != "1" ]; then
  if grep -Eq '(^|[[:space:]])file:|/stage/' composer.lock pnpm-lock.yaml; then
    echo "Public release locks must reference registry artifacts, not local staged packages." >&2
    exit 1
  fi
  require_grep "'@debugbundle/sdk-browser@3\\." pnpm-lock.yaml "WordPress 2.x must lock the hardened browser 3.x SDK"
fi
php -r '
$receipt = json_decode(file_get_contents("assets/dist/sdk-build.json"), true);
if (!is_array($receipt) || ($receipt["package"] ?? null) !== "@debugbundle/sdk-browser"
    || !preg_match(getenv("DEBUGBUNDLE_SOURCE_OVERLAY_SMOKE") === "1" ? "/^[23]\\./" : "/^3\\./", $receipt["version"] ?? "")
    || ($receipt["asset_sha256"] ?? null) !== hash_file("sha256", "assets/dist/debugbundle-browser.js")) {
    fwrite(STDERR, "Rebuild the browser 3.x asset and matching receipt from the locked dependency.\n");
    exit(1);
}'

rm -rf .dist
mkdir -p .dist/debugbundle

cp debugbundle.php .dist/debugbundle/
cp uninstall.php .dist/debugbundle/
cp readme.txt .dist/debugbundle/
cp README.md .dist/debugbundle/
cp MIGRATION-2.0.md .dist/debugbundle/
cp CHANGELOG.md .dist/debugbundle/
cp SECURITY.md .dist/debugbundle/
cp LICENSE .dist/debugbundle/
cp -R languages .dist/debugbundle/
cp -R src .dist/debugbundle/
mkdir -p .dist/debugbundle/assets
cp -R assets/dist .dist/debugbundle/assets/

if ! command -v composer >/dev/null 2>&1; then
  echo "Composer is required to build production vendor dependencies for release artifacts" >&2
  exit 1
fi

if [ -f .debugbundle-staged-candidate.json ]; then
  cp .debugbundle-staged-candidate.json .dist/debugbundle/STAGED-CANDIDATE.json
fi
cp composer.json .dist/debugbundle/
cp composer.lock .dist/debugbundle/
composer install \
  --working-dir=.dist/debugbundle \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist

# A source overlay is useful for local tests, but cannot certify a shipped dependency lock.
if [ ! -r .dist/debugbundle/vendor/debugbundle/sdk-php/src/TelemetryPrivacy.php ]; then
  echo "The Composer lock lacks the mandatory PHP privacy policy; publish and lock that SDK first." >&2
  exit 1
fi

if [ -n "${DEBUGBUNDLE_PHP_SDK_SOURCE:-}" ]; then
  if [ ! -r "$DEBUGBUNDLE_PHP_SDK_SOURCE/composer.json" ] ||
    ! grep -Eq '"name":[[:space:]]*"debugbundle/sdk-php"' "$DEBUGBUNDLE_PHP_SDK_SOURCE/composer.json"; then
    echo "DEBUGBUNDLE_PHP_SDK_SOURCE must point to a debugbundle/sdk-php checkout" >&2
    exit 1
  fi
  rm -rf .dist/debugbundle/vendor/debugbundle/sdk-php/src
  cp -R "$DEBUGBUNDLE_PHP_SDK_SOURCE/src" .dist/debugbundle/vendor/debugbundle/sdk-php/
fi

if [ ! -r .dist/debugbundle/vendor/debugbundle/sdk-php/src/TelemetryPrivacy.php ] ||
  [ ! -r .dist/debugbundle/vendor/debugbundle/sdk-php/src/BeforeSend.php ] ||
  ! grep -q 'beforeSend' .dist/debugbundle/vendor/debugbundle/sdk-php/src/DebugBundleSdk.php; then
  echo "The locked PHP SDK lacks mandatory telemetry privacy. Publish and lock the coordinated PHP SDK release before publishing this plugin." >&2
  exit 1
fi

find .dist/debugbundle/vendor -type d \( -name tests -o -name test -o -name examples -o -name smoke -o -name scripts -o -name .github \) -prune -exec rm -rf {} +
find .dist/debugbundle/vendor -type f \( -name Makefile -o -name phpunit.xml.dist -o -name phpstan.neon -o -name coverage.xml -o -name composer.phar -o -name composer-setup.php \) -delete

(
  cd .dist
  zip -rq "debugbundle-wordpress-${VERSION}.zip" debugbundle
  if command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "debugbundle-wordpress-${VERSION}.zip" > "debugbundle-wordpress-${VERSION}.zip.sha256"
  else
    sha256sum "debugbundle-wordpress-${VERSION}.zip" > "debugbundle-wordpress-${VERSION}.zip.sha256"
  fi
)

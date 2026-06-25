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

rm -rf .dist
mkdir -p .dist/debugbundle

cp debugbundle.php .dist/debugbundle/
cp uninstall.php .dist/debugbundle/
cp readme.txt .dist/debugbundle/
cp README.md .dist/debugbundle/
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

cp composer.json .dist/debugbundle/
cp composer.lock .dist/debugbundle/
composer install \
  --working-dir=.dist/debugbundle \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist

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

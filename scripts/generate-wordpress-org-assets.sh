#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
SOURCE_DIR="$REPO_DIR/.wordpress-org/source"
ASSET_DIR="$REPO_DIR/.wordpress-org/assets"

if ! command -v sips >/dev/null 2>&1; then
  echo "sips is required to generate WordPress.org assets on macOS" >&2
  exit 1
fi

mkdir -p "$ASSET_DIR"

render_png() {
  source_file=$1
  output_file=$2
  width=$3
  height=$4

  sips -s format png "$source_file" --out "$output_file" >/dev/null
  sips -z "$height" "$width" "$output_file" >/dev/null
}

cp "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon.svg"
render_png "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon-128x128.png" 128 128
render_png "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon-256x256.png" 256 256
render_png "$SOURCE_DIR/banner.svg" "$ASSET_DIR/banner-772x250.png" 772 250
render_png "$SOURCE_DIR/banner.svg" "$ASSET_DIR/banner-1544x500.png" 1544 500
render_png "$SOURCE_DIR/screenshot-1.svg" "$ASSET_DIR/screenshot-1.png" 1280 960
render_png "$SOURCE_DIR/screenshot-2.svg" "$ASSET_DIR/screenshot-2.png" 1280 960
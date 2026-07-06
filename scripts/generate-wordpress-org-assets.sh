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
rm -f \
  "$ASSET_DIR/banner-772x250.png" \
  "$ASSET_DIR/banner-1544x500.png" \
  "$ASSET_DIR/screenshot-1.png" \
  "$ASSET_DIR/screenshot-2.png"

render_png() {
  source_file=$1
  output_file=$2
  width=$3
  height=$4

  sips -s format png "$source_file" --out "$output_file" >/dev/null
  sips -z "$height" "$width" "$output_file" >/dev/null
}

render_jpg_cover() {
  source_file=$1
  output_file=$2
  width=$3
  height=$4

  source_width=$(sips -g pixelWidth "$source_file" | awk '/pixelWidth:/ { print int($2) }')
  source_height=$(sips -g pixelHeight "$source_file" | awk '/pixelHeight:/ { print int($2) }')

  if [ -z "$source_width" ] || [ -z "$source_height" ]; then
    echo "Could not read image dimensions for $source_file" >&2
    exit 1
  fi

  crop_width=$source_width
  crop_height=$source_height

  if [ $((source_width * height)) -gt $((source_height * width)) ]; then
    crop_width=$((source_height * width / height))
  else
    crop_height=$((source_width * height / width))
  fi

  offset_x=$(((source_width - crop_width) / 2))
  offset_y=$(((source_height - crop_height) / 2))

  tmp_file=$(mktemp "${TMPDIR:-/tmp}/debugbundle-wp-asset.XXXXXX.jpg")
  cp "$source_file" "$tmp_file"
  sips --cropToHeightWidth "$crop_height" "$crop_width" --cropOffset "$offset_y" "$offset_x" "$tmp_file" >/dev/null
  sips -s format jpeg "$tmp_file" --out "$output_file" >/dev/null
  sips -z "$height" "$width" "$output_file" >/dev/null
  rm -f "$tmp_file"
}

cp "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon.svg"
render_png "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon-128x128.png" 128 128
render_png "$SOURCE_DIR/icon.svg" "$ASSET_DIR/icon-256x256.png" 256 256
render_jpg_cover "$SOURCE_DIR/banner.jpg" "$ASSET_DIR/banner-772x250.jpg" 772 250
render_jpg_cover "$SOURCE_DIR/banner.jpg" "$ASSET_DIR/banner-1544x500.jpg" 1544 500
render_jpg_cover "$SOURCE_DIR/screenshot-1.jpg" "$ASSET_DIR/screenshot-1.jpg" 1280 960
render_jpg_cover "$SOURCE_DIR/screenshot-2.jpg" "$ASSET_DIR/screenshot-2.jpg" 1280 960

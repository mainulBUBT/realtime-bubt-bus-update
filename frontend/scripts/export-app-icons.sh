#!/bin/zsh

set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: ./scripts/export-app-icons.sh <driver|student> [output-dir]"
  exit 1
fi

APP_TYPE="$1"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SOURCE_SVG="$ROOT_DIR/public/icons/app-${APP_TYPE}.svg"
OUTPUT_DIR="${2:-$ROOT_DIR/resources/generated/$APP_TYPE}"

if [[ ! -f "$SOURCE_SVG" ]]; then
  echo "Icon source not found: $SOURCE_SVG"
  exit 1
fi

mkdir -p "$OUTPUT_DIR"

MASTER_PNG="$OUTPUT_DIR/icon-1024.png"

# Quick Look can rasterize SVG sources reliably on macOS.
qlmanage -t -s 1024 -o "$OUTPUT_DIR" "$SOURCE_SVG" >/dev/null

THUMBNAIL_PNG="$OUTPUT_DIR/$(basename "$SOURCE_SVG").png"

if [[ ! -f "$THUMBNAIL_PNG" ]]; then
  echo "Quick Look did not create the expected PNG export."
  exit 1
fi

mv "$THUMBNAIL_PNG" "$MASTER_PNG"

for size in 512 192 180 144 96 72 48; do
  sips -z "$size" "$size" "$MASTER_PNG" --out "$OUTPUT_DIR/icon-${size}.png" >/dev/null
done

typeset -A android_sizes=(
  [mdpi]=48
  [hdpi]=72
  [xhdpi]=96
  [xxhdpi]=144
  [xxxhdpi]=192
)

for density size in ${(kv)android_sizes}; do
  sips -z "$size" "$size" "$MASTER_PNG" --out "$OUTPUT_DIR/android-${density}.png" >/dev/null
done

echo "Exported ${APP_TYPE} icons to $OUTPUT_DIR"

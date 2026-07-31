#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M)"
OUT="$ROOT/hm-cpanel-deploy-$STAMP.zip"

cd "$ROOT"

zip -r "$OUT" \
    app \
    bootstrap \
    config \
    lang \
    public/css \
    public/js \
    public/fonts \
    public/images \
    public/index.php \
    public/.htaccess \
    resources/views \
    routes \
    artisan \
    composer.json \
    composer.lock \
    -x "*.DS_Store" "*/__MACOSX/*" 2>/dev/null || \
zip -r "$OUT" \
    app \
    bootstrap \
    config \
    lang \
    public/css \
    public/js \
    public/fonts \
    public/images \
    public/index.php \
    public/.htaccess \
    resources/views \
    routes \
    artisan \
    composer.json \
    -x "*.DS_Store" "*/__MACOSX/*"

echo ""
echo "Created: $OUT"
echo "Size:    $(du -h "$OUT" | cut -f1)"

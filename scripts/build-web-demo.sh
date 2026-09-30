#!/usr/bin/env bash
# Builds the public web demo (customer app with the in-browser demo backend) into
# ./app, which GitHub Pages serves at https://<user>.github.io/app/.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/customer-app"
flutter build web --release --dart-define=DEMO_MODE=true --base-href /app/ --no-web-resources-cdn
OUT="$ROOT/app"
rm -rf "$OUT"
cp -r build/web "$OUT"
# The JS build uses the CanvasKit renderer only: drop debug symbols and the
# WebAssembly-build renderers to keep the page light on mobile data.
find "$OUT/canvaskit" -name '*.symbols' -delete
rm -rf "$OUT"/canvaskit/skwasm* "$OUT"/canvaskit/wimp* "$OUT"/canvaskit/webparagraph
du -sh "$OUT"

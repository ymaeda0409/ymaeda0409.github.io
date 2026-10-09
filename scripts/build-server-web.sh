#!/usr/bin/env bash
# Builds the customer and rider apps for the web, talking to the API on the same
# host (`/api`), into deploy/web/{customer,driver}. deploy/server-setup.sh serves
# them at https://<host>/ and https://<host>/driver/.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAP_TILE_URL="${MAP_TILE_URL:-https://tile.openstreetmap.org/{z}/{x}/{y}.png}"

build() { # <app dir> <out name> <base href>
  cd "$ROOT/$1"
  flutter build web --release --base-href "$3" --no-web-resources-cdn \
    --dart-define=API_BASE_URL=/api --dart-define=MAP_TILE_URL="$MAP_TILE_URL"
  local out="$ROOT/deploy/web/$2"
  rm -rf "$out" && mkdir -p "$(dirname "$out")"
  cp -r build/web "$out"
  # CanvasKit (JS build) only: drop debug symbols and the WebAssembly renderers.
  find "$out/canvaskit" -name '*.symbols' -delete
  rm -rf "$out"/canvaskit/skwasm* "$out"/canvaskit/wimp* "$out"/canvaskit/webparagraph
  du -sh "$out"
}

build customer-app customer /
build driver-app driver /driver/

# Kitchen board / admin (Vue): built here so the server needs no Node.js.
cd "$ROOT/backend"
npx vite build
rm -rf "$ROOT/deploy/web/backend-build"
cp -r public/build "$ROOT/deploy/web/backend-build"
du -sh "$ROOT/deploy/web/backend-build"

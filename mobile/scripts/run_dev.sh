#!/usr/bin/env bash
# Run the app in the DEV flavor (development environment).
set -euo pipefail
cd "$(dirname "$0")/.."

flutter run \
  --flavor dev \
  --dart-define=APP_ENV=development \
  "$@"

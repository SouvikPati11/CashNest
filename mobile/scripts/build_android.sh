#!/usr/bin/env bash
# Build a release Android App Bundle (.aab) for a given flavor.
#
# Usage: scripts/build_android.sh [dev|staging|production]   (default: production)
set -euo pipefail
cd "$(dirname "$0")/.."

FLAVOR="${1:-production}"
case "$FLAVOR" in
  dev)        APP_ENV=development ;;
  staging)    APP_ENV=staging ;;
  production) APP_ENV=production ;;
  *) echo "Unknown flavor: $FLAVOR (use dev|staging|production)"; exit 1 ;;
esac

echo "Building Android App Bundle — flavor=$FLAVOR env=$APP_ENV"
flutter build appbundle \
  --release \
  --flavor "$FLAVOR" \
  --dart-define=APP_ENV="$APP_ENV" \
  --obfuscate \
  --split-debug-info=build/symbols/android/"$FLAVOR"

echo "Artifact: build/app/outputs/bundle/${FLAVOR}Release/"

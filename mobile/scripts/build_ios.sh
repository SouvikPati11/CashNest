#!/usr/bin/env bash
# Build a release iOS archive (IPA) for a given flavor.
#
# Usage: scripts/build_ios.sh [dev|staging|production]   (default: production)
# Requires a matching Xcode scheme per flavor (see RELEASE.md).
set -euo pipefail
cd "$(dirname "$0")/.."

FLAVOR="${1:-production}"
case "$FLAVOR" in
  dev)        APP_ENV=development ;;
  staging)    APP_ENV=staging ;;
  production) APP_ENV=production ;;
  *) echo "Unknown flavor: $FLAVOR (use dev|staging|production)"; exit 1 ;;
esac

echo "Building iOS IPA — flavor=$FLAVOR env=$APP_ENV"
flutter build ipa \
  --release \
  --flavor "$FLAVOR" \
  --dart-define=APP_ENV="$APP_ENV" \
  --obfuscate \
  --split-debug-info=build/symbols/ios/"$FLAVOR"

echo "Artifact: build/ios/ipa/"

#!/usr/bin/env bash
# bump-version.sh — Update version in all 4 project files
# Usage: ./scripts/bump-version.sh 3.0.3
#
# Files updated:
#   main.php         — Plugin header "Version: X.Y.Z"
#   readme.txt       — "Stable tag: X.Y.Z"
#   package.json     — "version": "X.Y.Z"
#   package-lock.json — "version": "X.Y.Z" (2 occurrences)

set -euo pipefail

if [ $# -ne 1 ]; then
    echo "Usage: $0 <new-version>"
    echo "Example: $0 3.0.3"
    exit 1
fi

NEW_VERSION="$1"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# Validate version format
if ! echo "$NEW_VERSION" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$'; then
    echo "Error: Version must be in X.Y.Z format (got: $NEW_VERSION)"
    exit 1
fi

# Get current version from main.php
CURRENT_VERSION=$(grep 'Version:' "$ROOT/main.php" | sed 's/.*Version:[[:space:]]*//' | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
if [ -z "$CURRENT_VERSION" ]; then
    echo "Error: Could not detect current version from main.php"
    exit 1
fi

if [ "$CURRENT_VERSION" = "$NEW_VERSION" ]; then
    echo "Version is already $NEW_VERSION"
    exit 0
fi

echo "Bumping version: $CURRENT_VERSION → $NEW_VERSION"

# main.php — plugin header
sed -i '' "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" "$ROOT/main.php"

# readme.txt — stable tag
sed -i '' "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEW_VERSION/" "$ROOT/readme.txt"

# package.json
sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" "$ROOT/package.json"

# package-lock.json (2 occurrences)
sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/g" "$ROOT/package-lock.json"

# Verify all files updated
VERIFY_ERRORS=0
for FILE in main.php readme.txt package.json package-lock.json; do
    if ! grep -q "$NEW_VERSION" "$ROOT/$FILE"; then
        echo "ERROR: $FILE was not updated"
        VERIFY_ERRORS=$((VERIFY_ERRORS + 1))
    fi
done

if [ $VERIFY_ERRORS -gt 0 ]; then
    echo "Version bump had errors. Check files manually."
    exit 1
fi

echo "Version bumped to $NEW_VERSION in all 4 files."
echo "Files updated:"
echo "  - main.php"
echo "  - readme.txt"
echo "  - package.json"
echo "  - package-lock.json"

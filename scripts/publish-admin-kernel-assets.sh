#!/usr/bin/env bash
set -e

SOURCE_DIR="Modules/AdminKernel/Ui/assets"
TARGET_DIR="public/admin/assets/maatify/admin-kernel"

echo "Publishing AdminKernel assets..."
echo "Source: $SOURCE_DIR"
echo "Target: $TARGET_DIR"

if [ ! -d "$SOURCE_DIR" ]; then
  echo "ERROR: Source assets directory does not exist."
  exit 1
fi

# Refuse to wipe the published target when the Kernel-owned source has no real
# assets yet (only .gitkeep or nothing at all). Assets are still host-owned at
# this stage, ahead of the Kernel's asset migration; publishing from an empty
# source would delete the live assets under $TARGET_DIR without replacing them.
REAL_SOURCE_FILES=$(find "$SOURCE_DIR" -type f ! -name '.gitkeep' | head -n1)
if [ -z "$REAL_SOURCE_FILES" ]; then
  echo "ERROR: $SOURCE_DIR has no real assets yet (only .gitkeep or empty)."
  echo "Refusing to wipe $TARGET_DIR. Populate the Kernel's own assets first."
  exit 1
fi

mkdir -p "$TARGET_DIR"

# Clean target directory before copy (safe because it's kernel-owned assets only)
rm -rf "$TARGET_DIR"/*

cp -R "$SOURCE_DIR"/. "$TARGET_DIR"/

echo "AdminKernel assets published successfully."

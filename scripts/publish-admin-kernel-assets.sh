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

mkdir -p "$TARGET_DIR"

# Clean target directory before copy (safe because it's kernel-owned assets only)
rm -rf "$TARGET_DIR"/*

cp -R "$SOURCE_DIR"/. "$TARGET_DIR"/

echo "AdminKernel assets published successfully."

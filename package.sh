#!/bin/bash
###############################################################################
# PrestaShop Membership Module - Package Script
#
# This script creates a properly structured ZIP file for PrestaShop module
# installation. The ZIP will contain the module files in a "membership/"
# folder, as required by PrestaShop.
#
# Usage: ./package.sh
# Output: membership.zip in the parent directory
###############################################################################

set -e

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_NAME="membership"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  PrestaShop Membership Module - Package Creator"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Change to parent directory
cd "$SCRIPT_DIR/.."
PARENT_DIR=$(pwd)

echo "📁 Current directory: $PARENT_DIR"
echo "📦 Module name: $MODULE_NAME"
echo ""

# Create temporary directory
TEMP_DIR="$PARENT_DIR/${MODULE_NAME}_temp"
echo "🔨 Creating temporary directory..."
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR/$MODULE_NAME"

# Copy files to temporary directory
echo "📋 Copying module files..."
cp -r "$SCRIPT_DIR/"* "$TEMP_DIR/$MODULE_NAME/" 2>/dev/null || true
cp -r "$SCRIPT_DIR/".??* "$TEMP_DIR/$MODULE_NAME/" 2>/dev/null || true

# Remove git directory and other unnecessary files
echo "🧹 Cleaning up unnecessary files..."
rm -rf "$TEMP_DIR/$MODULE_NAME/.git"
rm -rf "$TEMP_DIR/$MODULE_NAME/.gitignore"
rm -rf "$TEMP_DIR/$MODULE_NAME/.gitattributes"
rm -rf "$TEMP_DIR/$MODULE_NAME/.DS_Store"
rm -f "$TEMP_DIR/$MODULE_NAME/package.sh"

# Create ZIP file
ZIP_FILE="$PARENT_DIR/${MODULE_NAME}.zip"
rm -f "$ZIP_FILE"

echo "📦 Creating ZIP package..."
cd "$TEMP_DIR"
zip -r "$ZIP_FILE" "$MODULE_NAME/" -q

# Clean up temporary directory
echo "🧹 Cleaning up temporary files..."
cd "$PARENT_DIR"
rm -rf "$TEMP_DIR"

# Display results
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Package created successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📦 Package: $ZIP_FILE"
echo "📊 Size: $(du -h "$ZIP_FILE" | cut -f1)"
echo ""
echo "📥 To install in PrestaShop:"
echo "   1. Go to Modules > Module Manager"
echo "   2. Click 'Upload a module'"
echo "   3. Select: $ZIP_FILE"
echo "   4. Click 'Upload this module'"
echo ""
echo "📖 For detailed instructions, see INSTALL.md"
echo ""

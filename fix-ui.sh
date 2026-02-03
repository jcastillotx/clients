#!/bin/bash

# Quick UI Fix Script
# Run this on your server to fix missing styles and icons

set -e

echo "🔧 Fixing UI styling issues..."
echo ""

# Check if we're in a Laravel directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in a Laravel directory!"
    echo "Please cd to your Laravel app directory first."
    exit 1
fi

echo "📦 Installing Node dependencies..."
npm ci --prefer-offline --no-audit

echo ""
echo "🏗️  Building production assets..."
npm run build

echo ""
echo "🗑️  Clearing Laravel caches..."
php artisan view:clear
php artisan cache:clear

echo ""
echo "🔐 Setting permissions..."
chmod -R 755 public/build 2>/dev/null || true

echo ""
echo "✅ Verifying build..."
if [ -d "public/build" ]; then
    echo "✓ public/build directory exists"
    if [ -f "public/build/manifest.json" ]; then
        echo "✓ Vite manifest found"
    else
        echo "⚠️  Warning: manifest.json not found"
    fi
    
    if [ -d "public/build/assets" ]; then
        echo "✓ Assets directory exists"
        echo "  Files:"
        ls -lh public/build/assets/ | tail -n +2
    else
        echo "⚠️  Warning: assets directory not found"
    fi
else
    echo "❌ Error: public/build directory was not created!"
    exit 1
fi

echo ""
echo "🎨 UI fix complete!"
echo ""
echo "Next steps:"
echo "1. Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)"
echo "2. Check that styles and icons are now loading"
echo "3. If issues persist, check browser console (F12) for errors"
echo ""

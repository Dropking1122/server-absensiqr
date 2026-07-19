#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

# Build assets jika belum ada
if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

# Jalankan artisan serve di port 5000
php artisan config:clear
php artisan serve --host=0.0.0.0 --port=5000

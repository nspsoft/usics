#!/bin/bash
set -euo pipefail

PROJECT_PATH="/www/wwwroot/jicos.jidoka.co.id"
PHP_BIN="/www/server/php/84/bin/php"
BUILD_DIR="$PROJECT_PATH/public/build"
BUILD_TEMP_DIR="$PROJECT_PATH/public/build-next"
BUILD_BACKUP_DIR="$PROJECT_PATH/public/build-backup"

cd "$PROJECT_PATH" || { echo "Directory not found! ($PROJECT_PATH)"; exit 1; }

echo "--- 🔄 MULAI SINKRONISASI (Updated via Git) ---"

restore_previous_build() {
  if [ -d "$BUILD_BACKUP_DIR" ] && [ ! -d "$BUILD_DIR" ]; then
    mv "$BUILD_BACKUP_DIR" "$BUILD_DIR"
    echo "--- ♻️ BUILD LAMA DIRESTORE ---"
  fi

  rm -rf "$BUILD_TEMP_DIR"
}

git fetch --all
git reset --hard origin/main

composer install --no-dev --optimize-autoloader

$PHP_BIN artisan migrate --force
# $PHP_BIN artisan purchase-order:fix-creator

if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

rm -rf "$BUILD_TEMP_DIR" "$BUILD_BACKUP_DIR"

if [ -d "$BUILD_DIR" ]; then
  cp -a "$BUILD_DIR" "$BUILD_BACKUP_DIR"
fi

trap restore_previous_build ERR

node --max-old-space-size=4096 ./node_modules/vite/bin/vite.js build --outDir public/build-next --emptyOutDir

if [ ! -f "$BUILD_TEMP_DIR/manifest.json" ]; then
  echo "Vite manifest tidak ditemukan setelah build."
  exit 1
fi

rm -rf "$BUILD_DIR"
mv "$BUILD_TEMP_DIR" "$BUILD_DIR"
rm -rf "$BUILD_BACKUP_DIR"

trap - ERR

$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan app:generate-favicons || true
$PHP_BIN artisan queue:restart || true

chown -R www:www "$PROJECT_PATH/storage" || true
chown -R www:www "$PROJECT_PATH/bootstrap/cache" || true
chown -R www:www "$BUILD_DIR" || true
chmod -R 775 "$PROJECT_PATH/storage" || true
chmod -R 775 "$PROJECT_PATH/bootstrap/cache" || true
chmod -R 775 "$BUILD_DIR" || true

echo "--- ✅ DEPLOY SELESAI & AMAN ---"

#!/bin/bash
set -euo pipefail

PROJECT_PATH="/www/wwwroot/jicos.jidoka.co.id"
PHP_BIN="/www/server/php/84/bin/php"

cd "$PROJECT_PATH" || { echo "Directory not found! ($PROJECT_PATH)"; exit 1; }

echo "--- 🔄 MULAI SINKRONISASI (Updated via Git) ---"

git fetch --all
git reset --hard origin/main

composer install --no-dev --optimize-autoloader

$PHP_BIN artisan migrate --force
$PHP_BIN artisan purchase-order:fix-creator

if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

npm run build

$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan app:generate-favicons || true
$PHP_BIN artisan queue:restart || true

chown -R www:www "$PROJECT_PATH" || true
chmod -R 775 "$PROJECT_PATH/storage" || true
chmod -R 775 "$PROJECT_PATH/bootstrap/cache" || true

echo "--- ✅ DEPLOY SELESAI & AMAN ---"

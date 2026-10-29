#!/usr/bin/env bash
# Pasang perubahan terbaru di server ini (Caddy → PHP-FPM pool "kamalan").
# Pakai: deploy/deploy.sh            (tanpa git pull)
#        deploy/deploy.sh --pull     (ambil dulu dari origin, hanya fast-forward)
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ "${1:-}" == "--pull" ]]; then
  git pull --ff-only
fi

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
npm ci --no-audit --no-fund
npm run build

# Cache rute, view, event, komponen Filament. Config sengaja TIDAK di-cache: dengan config:cache,
# `php artisan test` di server ini akan memakai database dan R2 asli, bukan penyimpanan tes.
php artisan optimize:clear
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
rm -f bootstrap/cache/catalog.php   # katalog dibangun ulang dari database pada permintaan berikutnya

# Muat ulang worker PHP-FPM (opcache ikut bersih), tanpa memutus permintaan yang sedang berjalan.
sudo systemctl reload php8.4-fpm

echo "Deploy selesai: $(git log --oneline -1)"

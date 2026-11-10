#!/usr/bin/env bash
# Setup Manajemen Produksi (Laravel) untuk Linux / macOS. Pastikan MySQL menyala.
set -e
cd "$(dirname "$0")"
if [ ! -f vendor/autoload.php ]; then
  command -v composer >/dev/null || { echo "Composer belum terpasang: https://getcomposer.org"; exit 1; }
  composer install --no-dev --optimize-autoloader --no-interaction
fi
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
[ -f .env ] || cp .env.example .env
php artisan key:generate --force
php artisan migrate --force          # membuat database produksi bila belum ada
php artisan migrate:fresh --seed --force
php artisan optimize:clear
chmod -R ug+rw storage bootstrap/cache
echo "Selesai. Jalankan: php artisan serve  lalu buka http://127.0.0.1:8000"
echo "Masuk:  admin@produksi.test / admin123   ppic@produksi.test / ppic123"
echo "        gudang@produksi.test / gudang123   operator@produksi.test / operator123"

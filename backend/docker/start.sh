#!/bin/sh
# تشغيل الحاوية: المنفذ من الاستضافة، تجهيز الكاش، الترحيلات، ثم Apache.
set -e

PORT="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# شهادة قاعدة البيانات (Aiven MySQL يتطلب SSL بشهادته): محتوى ca.pem بـ Base64
if [ -n "${DB_SSL_CA_BASE64:-}" ]; then
  echo "$DB_SSL_CA_BASE64" | base64 -d > /tmp/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/tmp/db-ca.pem
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize || true

# الترحيلات في كل تشغيل (آمنة — تطبّق الجديد فقط)
php artisan migrate --force

# أول تشغيل: الكتالوج وحساب المدير (من SEED_ADMIN_EMAIL و SEED_ADMIN_PASSWORD)
if [ "${SEED_ON_START:-false}" = "true" ]; then
  php artisan db:seed --force
fi

exec apache2-foreground

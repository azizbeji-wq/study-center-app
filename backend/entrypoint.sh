#!/bin/sh
set -e


# Ensure storage directories exist and have proper permissions
mkdir -p /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/framework/cache \
         /var/www/storage/logs \
         /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Auto-generate APP_KEY if not provided
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force || true
fi

# Run migrations and seed test data
echo "Running migrations..."
php artisan migrate --force

echo "Seeding default data (if needed)..."
php artisan db:seed --force || true

# Start server on Render port (defaults to 10000 or 8000)
PORT=${PORT:-8000}
echo "Starting Laravel server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

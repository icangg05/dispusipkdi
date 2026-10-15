#!/bin/sh
# Prepares the Laravel app on container start: dependencies, .env, key, database, storage link.
set -e

cd /var/www/html

if [ "${SKIP_BOOTSTRAP:-false}" = "true" ]; then
    exec "$@"
fi

if [ ! -f vendor/autoload.php ]; then
    echo "[dispusip] composer install"
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "[dispusip] membuat .env dari .env.example"
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

echo "[dispusip] menunggu database ${DB_HOST:-db}:${DB_PORT:-3306}"
php -r '
$host = getenv("DB_HOST") ?: "db";
$port = getenv("DB_PORT") ?: "3306";
$db = getenv("DB_DATABASE") ?: "dispusip";
for ($i = 0; $i < 60; $i++) {
    try {
        new PDO("mysql:host=$host;port=$port;dbname=$db", getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        exit(0);
    } catch (Throwable $e) {
        sleep(2);
    }
}
fwrite(STDERR, "Database tidak dapat dihubungi.\n");
exit(1);
'

php artisan migrate --force --no-interaction

if [ "${SEED_ON_FIRST_RUN:-true}" = "true" ] && [ ! -f storage/app/.seeded ]; then
    echo "[dispusip] mengisi data awal"
    php artisan db:seed --force --no-interaction
    touch storage/app/.seeded
fi

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

# Production (compose.prod.yml): cache config, routes, views and events once the .env is in place.
if [ "${OPTIMIZE_ON_START:-false}" = "true" ]; then
    php artisan optimize --no-interaction
fi

exec "$@"

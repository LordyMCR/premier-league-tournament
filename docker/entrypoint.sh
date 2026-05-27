#!/bin/sh
set -e

cd /var/www/html

wait_for_database() {
    if [ -z "$DB_HOST" ] || [ "$DB_CONNECTION" != "pgsql" ]; then
        return 0
    fi

    echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
    until php -r "
        try {
            new PDO(
                'pgsql:host=${DB_HOST};port=${DB_PORT:-5432};dbname=${DB_DATABASE}',
                '${DB_USERNAME}',
                '${DB_PASSWORD}'
            );
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    "; do
        sleep 2
    done
    echo "Database is ready."
}

wait_for_database

if [ ! -f vendor/autoload.php ] && [ -f composer.json ]; then
    echo "Installing PHP dependencies..."
    composer install --prefer-dist --no-interaction
fi

php artisan storage:link --force 2>/dev/null || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"

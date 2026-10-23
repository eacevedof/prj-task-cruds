#!/bin/sh
# docker/fpm/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de php-fpm:
# .env si falta, vendor al dia y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env
composer install --no-interaction --no-progress

# como www-data (el usuario de php-fpm): si la .sqlite la crea root queda 644 y fpm no puede escribir
chown www-data:www-data database 2>/dev/null || true
su -s /bin/sh www-data -c "cd /app/console && php console.php run-migrations"

exec docker-php-entrypoint "$@"

#!/bin/sh
# docker/app/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de la API:
# .env si falta, dependencias al dia y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env

# node_modules es un volumen: npm ci solo si package-lock.json ha cambiado desde la ultima instalacion
if ! cmp -s package-lock.json node_modules/.installed-package-lock.json; then
    npm ci --no-audit --no-fund
    cp package-lock.json node_modules/.installed-package-lock.json
fi

node cmd/console/main.ts run-migrations

exec "$@"

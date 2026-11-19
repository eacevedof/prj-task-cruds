#!/bin/sh
# docker/app/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de la API:
# .env si falta y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env

python cmd/console/main.py run-migrations

exec "$@"

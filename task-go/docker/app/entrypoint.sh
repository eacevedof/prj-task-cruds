#!/bin/sh
# docker/app/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de la API:
# .env si falta, binarios al dia y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env
go build -o build/api ./cmd/api
go build -o build/console ./cmd/console

./build/console run-migrations

exec "$@"

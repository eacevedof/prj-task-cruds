#!/bin/sh
# docker/app/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de la API:
# .env si falta, binarios al dia y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env
cargo build --release --bins
cp "$CARGO_TARGET_DIR/release/api" "$CARGO_TARGET_DIR/release/console" build/

./build/console run-migrations

exec "$@"

#!/bin/sh
# docker/app/entrypoint.sh -- lo que tiene que pasar en CADA arranque del contenedor, antes de la API:
# .env si falta, compilacion al dia y migraciones pendientes. Idempotente: si no hay nada que hacer, no hace nada.
set -e

cd /app
[ -f .env ] || cp .env.example .env
# con un checkout CRLF (Windows) los lanzadores no arrancarian
sed -i 's/\r$//' bin/api bin/console
# las dependencias viven en el volumen de ~/.m2: solo se descargan la primera vez
mvn -q -B package -DskipTests

bin/console run-migrations

exec "$@"

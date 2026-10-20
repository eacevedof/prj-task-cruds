#docker/fpm/Dockerfile
# alpine + solo lo que trae la imagen oficial (pdo_sqlite y sqlite3 vienen compilados de serie).
FROM php:8.4.23-fpm-alpine

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Composer con version fijada desde la imagen oficial: reproducible y sin descarga insegura.
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

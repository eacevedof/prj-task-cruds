#docker/fpm/Dockerfile
# alpine + solo lo que trae la imagen oficial (pdo_sqlite y sqlite3 vienen compilados de serie).
FROM php:8.5.12-fpm-alpine

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# mail(): el sendmail de busybox entrega por smtp al contenedor de Mailpit (docker-compose.yml).
RUN echo 'sendmail_path = "/usr/sbin/sendmail -t -i -S cont-task-php-mailpit:1025"' > "$PHP_INI_DIR/conf.d/zz-mail.ini"

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae. libstdc++: lo exige el VS Code Server en alpine (Dev Containers, .devcontainer/task-php).
RUN apk upgrade --no-cache && apk add --no-cache libstdc++

# Composer con version fijada desde la imagen oficial: reproducible y sin descarga insegura.
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# arranque: .env + composer install + migraciones en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint
ENTRYPOINT ["task-entrypoint"]
CMD ["php-fpm"]

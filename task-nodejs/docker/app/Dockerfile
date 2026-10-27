#docker/app/Dockerfile
# node alpine: ejecuta la API y la consola en TypeScript sin compilar (Node 24 quita los tipos al cargar el .ts).
# SQLite es el node:sqlite del propio Node: sin dependencias nativas.
FROM node:24.22.1-alpine

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae.
# El sendmail de busybox (/usr/sbin/sendmail) ya viene en alpine: APP_SENDMAIL_PATH lo apunta a Mailpit.
RUN apk upgrade --no-cache

WORKDIR /app

# arranque: .env + dependencias + migraciones en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint
ENTRYPOINT ["task-entrypoint"]
CMD ["node", "cmd/api/main.ts", "--addr=0.0.0.0:8080"]

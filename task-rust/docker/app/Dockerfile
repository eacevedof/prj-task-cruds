#docker/app/Dockerfile
# rust alpine: compila y ejecuta la API y la consola (SQLite va compilado dentro del binario: rusqlite bundled, con el
# gcc y musl-dev que ya trae la imagen).
FROM rust:1.99.1-alpine

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae. libstdc++: lo exige el VS Code Server en alpine (Dev Containers, .devcontainer/task-rust).
# El sendmail de busybox (/usr/sbin/sendmail) ya viene en alpine: APP_SENDMAIL_PATH lo apunta a Mailpit.
RUN apk upgrade --no-cache && apk add --no-cache libstdc++

# la compilacion fuera de /app (un volumen): el bind mount de Windows es lento y asi el build de cada arranque es incremental
ENV CARGO_TARGET_DIR=/cargo-target

WORKDIR /app

# arranque: .env + build + migraciones en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint
ENTRYPOINT ["task-entrypoint"]
CMD ["/app/build/api", "--addr=0.0.0.0:8080"]

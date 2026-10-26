#docker/app/Dockerfile
# golang alpine: compila y ejecuta la API y la consola (sqlite en Go puro, sin cgo).
FROM golang:1.26.10-alpine

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae. libstdc++: lo exige el VS Code Server en alpine (Dev Containers, .devcontainer/task-go).
# El sendmail de busybox (/usr/sbin/sendmail) ya viene en alpine: APP_SENDMAIL_PATH lo apunta a Mailpit.
RUN apk upgrade --no-cache && apk add --no-cache libstdc++

ENV CGO_ENABLED=0 \
    GOFLAGS=-buildvcs=false

WORKDIR /app

# arranque: .env + build + migraciones en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint
ENTRYPOINT ["task-entrypoint"]
CMD ["/app/build/api", "-addr=:8080"]

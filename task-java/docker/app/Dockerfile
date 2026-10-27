#docker/app/Dockerfile
# maven + temurin 25 (alpine): compila y ejecuta la API y la consola. SQLite va dentro de sqlite-jdbc (nativa incluida).
FROM maven:3.9.16-eclipse-temurin-25-alpine

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae. libstdc++: lo exige el VS Code Server en alpine (Dev Containers, .devcontainer/task-java).
# El sendmail de busybox (/usr/sbin/sendmail) ya viene en alpine: APP_SENDMAIL_PATH lo apunta a Mailpit.
RUN apk upgrade --no-cache && apk add --no-cache libstdc++

WORKDIR /app

# arranque: .env + mvn package + migraciones en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint
ENTRYPOINT ["task-entrypoint"]
CMD ["/app/bin/api", "--addr=0.0.0.0:8080"]

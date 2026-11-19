# docker/app/Dockerfile -- contenedor LOCAL de la API en Python (no va a prod). Contexto de build: task-python/.
# Todo el toolchain vive aqui: en el host no se instala Python ni sus dependencias (patron de docker/go de eduardoaf.com).
#   make image   -> construye con tu UID/GID (los ficheros que escribe la API -BD, logs- salen con tu usuario)
FROM python:3.15.2-alpine

ARG UID=1000
ARG GID=1000

# apk upgrade: los parches de seguridad de alpine que la imagen aun no trae.
# El sendmail de busybox (/usr/sbin/sendmail) ya viene en alpine: APP_SENDMAIL_PATH lo apunta a Mailpit.
RUN apk upgrade --no-cache \
    && apk add --no-cache tzdata \
    && (getent group "${GID}" || addgroup -g "${GID}" dev) \
    && adduser -D -u "${UID}" -G "$(getent group "${GID}" | cut -d: -f1)" -s /bin/sh dev

# pip y setuptools de la imagen base llevan CVEs HIGH (trivy, 2026-11-18): se actualizan antes de instalar.
# tzdata: zoneinfo (zona horaria del usuario) no encuentra zonas en alpine sin ella.
# dependencias en la imagen (cambiar requirements.txt = make rebuild). Fijadas en requirements.txt.
COPY requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir --root-user-action=ignore -U pip setuptools \
    && pip install --no-cache-dir --root-user-action=ignore -r /tmp/requirements.txt && rm /tmp/requirements.txt

# src/ (Core, Modules, Console) y la raiz (console/, tests/) en el path de imports; sin .pyc en el fuente montado
ENV PYTHONPATH=/app/src:/app \
    PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

# arranque: .env si falta + migraciones pendientes en cada up (ver entrypoint.sh).
# sed: el checkout en Windows (autocrlf) deja CRLF y el shebang no se ejecutaria.
COPY docker/app/entrypoint.sh /usr/local/bin/task-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/task-entrypoint && chmod +x /usr/local/bin/task-entrypoint

USER dev
WORKDIR /app
ENTRYPOINT ["task-entrypoint"]
CMD ["python", "cmd/api/main.py", "--addr=0.0.0.0:8080"]

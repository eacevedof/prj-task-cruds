#!/bin/bash
# Stack local (api en Python + mailpit). Se lanza desde task-python/. docker/.env es opcional (defaults en el compose).
# Todo en contenedor: en el host no se instala Python. En vnic-eaf-001 docker va con sudo: make up DOCKER="sudo docker"
# Cada arranque de cont-task-python-app ya hace .env + migraciones (docker/app/entrypoint.sh):
# el target migrate queda para relanzarlas a mano sin reiniciar. Las dependencias van HORNEADAS en la imagen
# (requirements.txt): cambiarlas = make rebuild.
DOCKER ?= docker
# con tu UID/GID los ficheros que escribe la API (BD, logs) salen con tu usuario
export UID := $(shell id -u)
export GID := $(shell id -g)
DC := $(DOCKER) compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
APP := cont-task-python-app
HTTP_PORT ?= 9800
CURRENT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
# ramas que sincroniza update-branch (este repo solo tiene main): make update-branch BRANCHES="main dev"
BRANCHES ?= main

# targets que no son ficheros: sin esto "make console" o "make test" no hacen nada, porque existen esas carpetas
.PHONY: help env start up down restart rebuild ps logs config ssh-app ssh-be console migrate test audit health update-branch gitpush

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

env: ## crea .env y docker/.env desde sus .example (no pisa los existentes) · make env
	@test -f .env || cp .env.example .env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up ## primer arranque: env + contenedores (las migraciones van en el arranque) · make start
	@echo "http://localhost:$(HTTP_PORT)"

up: ## levanta los contenedores (+ migraciones) · make up
	$(DC) up -d
	$(DOCKER) ps --filter name=task-python

down: ## para y elimina los contenedores · make down
	$(DC) down

restart: ## reinicia los contenedores (relee el codigo y pasa las migraciones: asi se aplican los cambios) · make restart
	$(DC) restart

rebuild: ## reconstruye la imagen (dependencias de requirements.txt, parches de alpine) y relanza · make rebuild
	$(DC) down
	$(DC) up -d --build --remove-orphans

ps: ## contenedores del stack · make ps
	$(DOCKER) ps --filter name=task-python

logs: ## logs de los contenedores (follow) · make logs
	$(DC) logs -f

config: ## compose resuelto · make config
	$(DC) config

ssh-app: ## shell en el contenedor de la api · make ssh-app
	$(DOCKER) exec -it $(APP) sh

ssh-be: ## shell en el contenedor de la api, en /app y mostrando el help de la consola · make ssh-be
	$(DOCKER) exec -it -w /app $(APP) sh -c "python cmd/console/main.py help; exec sh"

console: ## comando de consola · make console c="help"  |  make console c='user-create --user_name=ana.lopez --user_email=ana@example.com --password=Ana.Secret1'
	$(DOCKER) exec -it -w /app $(APP) python cmd/console/main.py $(c)

migrate: ## migraciones .sql pendientes a mano (database/migrations) · make migrate
	$(DOCKER) exec -w /app $(APP) python cmd/console/main.py run-migrations

test: ## tests contra su propia BD (database/task_cruds_test-<test>.sqlite) · make test  |  make test f="GraphQl"
	$(DOCKER) exec -w /app $(APP) python -m unittest discover -s tests -t . -p "*Test.py" $(if $(f),-k $(f),)

audit: ## vulnerabilidades conocidas de las dependencias (pip-audit, contenedor temporal) · make audit
	$(DOCKER) run --rm --user 0 -v $(CURDIR)/requirements.txt:/r.txt:ro python:3.15.2-alpine sh -c "pip install -q --root-user-action=ignore pip-audit && pip-audit -r /r.txt"

health: ## health-check del front controller · make health
	curl -s http://localhost:$(HTTP_PORT)/health

update-branch: ## resetea las ramas BRANCHES a su origin y vuelve a la actual · make update-branch BRANCHES="main"
	clear;
	git fetch --all;
	@for b in $(BRANCHES); do git checkout -f $$b && git reset --hard origin/$$b || exit 1; done
	git checkout -f $(CURRENT_BRANCH);

gitpush: ## git push · make gitpush m="mensaje"
	clear;
	git pull;
	git add .; git commit -m "python: $(m)"; git push;

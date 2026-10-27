#!/bin/bash
# Stack local (api en Node.js + mailpit). Se lanza desde task-nodejs/. docker/.env es opcional (defaults en el compose).
# Cada arranque de cont-task-nodejs-app ya hace .env + npm ci (si cambia el lock) + migraciones (docker/app/entrypoint.sh):
# los targets install y migrate quedan para relanzarlos a mano sin reiniciar.
DC := docker compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
APP := cont-task-nodejs-app
HTTP_PORT ?= 9600
CURRENT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
# ramas que sincroniza update-branch (este repo solo tiene main): make update-branch BRANCHES="main dev"
BRANCHES ?= main

# targets que no son ficheros: sin esto "make console" o "make test" no hacen nada, porque existen esas carpetas
.PHONY: help env start up down restart rebuild ps logs config ssh-app ssh-be install console migrate test typecheck health update-branch gitpush

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

env: ## crea .env y docker/.env desde sus .example (no pisa los existentes) · make env
	@test -f .env || cp .env.example .env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up ## primer arranque: env + contenedores (dependencias y migraciones van en el arranque) · make start
	@echo "http://localhost:$(HTTP_PORT)"

up: ## levanta los contenedores (+ npm ci y migraciones) · make up
	$(DC) up -d
	docker ps --filter name=task-nodejs

down: ## para y elimina los contenedores · make down
	$(DC) down

restart: ## reinicia los contenedores (relee el codigo y pasa las migraciones: asi se aplican los cambios) · make restart
	$(DC) restart

rebuild: ## reconstruye las imagenes y relanza · make rebuild
	$(DC) down
	$(DC) up -d --build --remove-orphans

ps: ## contenedores del stack · make ps
	docker ps --filter name=task-nodejs

logs: ## logs de los contenedores (follow) · make logs
	$(DC) logs -f

config: ## compose resuelto · make config
	$(DC) config

ssh-app: ## shell en el contenedor de la api · make ssh-app
	docker exec -it $(APP) sh

ssh-be: ## shell en el contenedor de la api, en /app y mostrando el help de la consola · make ssh-be
	docker exec -it -w /app $(APP) sh -c "node cmd/console/main.ts help; exec sh"

install: ## reinstala las dependencias (npm ci) dentro del contenedor · make install
	docker exec $(APP) sh -c 'npm ci --no-audit --no-fund && cp package-lock.json node_modules/.installed-package-lock.json'

console: ## comando de consola · make console c="help"  |  make console c='user-create --user_name=ana.lopez --user_email=ana@example.com --password=Ana.Secret1'
	docker exec -it -w /app $(APP) node cmd/console/main.ts $(c)

migrate: ## migraciones .sql pendientes a mano (database/migrations) · make migrate
	docker exec -w /app $(APP) node cmd/console/main.ts run-migrations

test: ## tests contra su propia BD (database/task_cruds_test-<test>.sqlite) · make test  |  make test f="Graphql"
	docker exec -w /app $(APP) node --test $(if $(f),--test-name-pattern="$(f)",) "tests/**/*Test.ts"

typecheck: ## tsc --noEmit (TypeScript estricto) · make typecheck
	docker exec -w /app $(APP) npx tsc --noEmit

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
	git add .; git commit -m "nodejs: $(m)"; git push;

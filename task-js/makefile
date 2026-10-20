#!/bin/bash
# SPA task-js (nginx). Se lanza desde task-js/. docker/.env es opcional (defaults en el compose).
# Consume la API de task-php: tiene que estar levantada (cd ../task-php && make up).
DC := docker compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
WEB := cont-task-js-web
HTTP_PORT ?= 9200
CURRENT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
# ramas que sincroniza update-branch (este repo solo tiene main): make update-branch BRANCHES="main dev"
BRANCHES ?= main

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

env: ## crea docker/.env desde su .example (no lo pisa) · make env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up ## primer arranque: env + contenedor · make start
	@echo "http://localhost:$(HTTP_PORT)"

up: ## levanta el contenedor · make up
	$(DC) up -d
	docker ps --filter name=task-js

down: ## para y elimina el contenedor · make down
	$(DC) down

restart: ## reinicia el contenedor (relee la plantilla de nginx y API_BASE_URL) · make restart
	$(DC) restart

ps: ## contenedor del stack · make ps
	docker ps --filter name=task-js

logs: ## logs del contenedor (follow) · make logs
	$(DC) logs -f

config: ## compose resuelto · make config
	$(DC) config

ssh-web: ## shell en nginx · make ssh-web
	docker exec -it $(WEB) sh

health: ## la SPA y su configuracion responden · make health
	curl -s -o /dev/null -w "spa %{http_code}\n" http://localhost:$(HTTP_PORT)/
	curl -s http://localhost:$(HTTP_PORT)/config.json

update-branch: ## resetea las ramas BRANCHES a su origin y vuelve a la actual · make update-branch BRANCHES="main"
	clear;
	git fetch --all;
	@for b in $(BRANCHES); do git checkout -f $$b && git reset --hard origin/$$b || exit 1; done
	git checkout -f $(CURRENT_BRANCH);

gitpush: ## git push · make gitpush m="mensaje"
	clear;
	git pull;
	git add .; git commit -m "js: $(m)"; git push;
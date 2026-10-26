#!/bin/bash
# SPA task-webassembly (Go -> main.wasm + nginx). Se lanza desde task-webassembly/. docker/.env es opcional (defaults en el compose).
# Cada up compila main.wasm (contenedor task-webassembly-build) antes de arrancar nginx.
# Consume la API de task-php o de task-go (se elige en la interfaz): la elegida tiene que estar levantada (make up en su carpeta).
DC := docker compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
WEB := cont-task-webassembly-web
BUILD := task-webassembly-build
HTTP_PORT ?= 9400
CURRENT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
# ramas que sincroniza update-branch (este repo solo tiene main): make update-branch BRANCHES="main dev"
BRANCHES ?= main

# targets que no son ficheros
.PHONY: help env start up down restart build vet ps logs config ssh-web health update-branch gitpush

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

env: ## crea docker/.env desde su .example (no lo pisa) · make env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up ## primer arranque: env + build de main.wasm + nginx · make start
	@echo "http://localhost:$(HTTP_PORT)"

up: ## compila main.wasm y levanta nginx · make up
	$(DC) up -d
	docker ps --filter name=task-webassembly

down: ## para y elimina los contenedores · make down
	$(DC) down

restart: build ## recompila main.wasm y reinicia nginx (relee la plantilla y API_BASE_URL_*) · make restart
	$(DC) restart task-webassembly-web

build: ## recompila main.wasm (y wasm_exec.js) sin reiniciar nginx: basta con recargar la pagina · make build
	$(DC) run --rm $(BUILD)

vet: ## go vet del codigo Go para js/wasm · make vet
	$(DC) run --rm $(BUILD) go vet ./...

ps: ## contenedores del stack · make ps
	docker ps -a --filter name=task-webassembly

logs: ## logs de los contenedores (follow) · make logs
	$(DC) logs -f

config: ## compose resuelto · make config
	$(DC) config

ssh-web: ## shell en nginx · make ssh-web
	docker exec -it $(WEB) sh

health: ## la SPA, main.wasm y su configuracion responden · make health
	curl -s -o /dev/null -w "spa %{http_code}\n" http://localhost:$(HTTP_PORT)/
	curl -s -o /dev/null -w "wasm %{http_code} %{content_type}\n" http://localhost:$(HTTP_PORT)/wasm/main.wasm
	curl -s http://localhost:$(HTTP_PORT)/config.json

update-branch: ## resetea las ramas BRANCHES a su origin y vuelve a la actual · make update-branch BRANCHES="main"
	clear;
	git fetch --all;
	@for b in $(BRANCHES); do git checkout -f $$b && git reset --hard origin/$$b || exit 1; done
	git checkout -f $(CURRENT_BRANCH);

gitpush: ## git push · make gitpush m="mensaje"
	clear;
	git pull;
	git add .; git commit -m "wasm: $(m)"; git push;

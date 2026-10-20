#!/bin/bash
# Stack local (php-fpm + nginx). Se lanza desde task-php/. docker/.env es opcional (defaults en el compose).
DC := docker compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
FPM := task-php-fpm
HTTP_PORT ?= 9100

help: ## Show this help message
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@egrep "^(.+)\:\ ##\ (.+)" ${MAKEFILE_LIST} | column -t -c 2 -s ":#"

env: ## crea .env y docker/.env desde sus .example (no pisa los existentes)
	@test -f .env || cp .env.example .env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up composer-install ## primer arranque: env + contenedores + vendor
	@echo "http://localhost:$(HTTP_PORT)"

up: ## levanta los contenedores
	$(DC) up -d
	docker ps --filter name=task-php

down: ## para y elimina los contenedores
	$(DC) down

restart: ## reinicia los contenedores
	$(DC) restart

rebuild: ## reconstruye las imagenes y relanza
	$(DC) down
	$(DC) up -d --build --remove-orphans

ps: ## contenedores del stack
	docker ps --filter name=task-php

logs: ## logs de los contenedores (follow)
	$(DC) logs -f

config: ## compose resuelto
	$(DC) config

ssh-fpm: ## shell en php-fpm
	docker exec -it $(FPM) sh

ssh-nginx: ## shell en nginx
	docker exec -it task-php-nginx sh

composer-install: ## composer install dentro de php-fpm
	docker exec $(FPM) composer install --no-interaction

console: ## comando de consola: make console c="help"
	docker exec -it -w /app/console $(FPM) php console.php $(c)

migrate: ## ejecuta las migraciones .sql pendientes (database/migrations)
	docker exec -w /app/console $(FPM) php console.php run-migrations

health: ## health-check del front controller
	curl -s http://localhost:$(HTTP_PORT)/

gitpush: ## git push
	clear;
	git pull;
	git add .; git commit -m "php: $(m)"; git push;

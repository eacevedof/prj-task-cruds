#!/bin/bash
# Stack local (php-fpm + nginx). Se lanza desde task-php/. docker/.env es opcional (defaults en el compose).
# Cada arranque de cont-task-php-fpm ya hace .env + composer install + migraciones (docker/fpm/entrypoint.sh):
# los targets composer-install y migrate quedan para relanzarlos a mano sin reiniciar.
DC := docker compose $(if $(wildcard ./docker/.env),--env-file ./docker/.env,) -f docker/docker-compose.yml
FPM := cont-task-php-fpm
# console/migrate corren como www-data (el usuario de php-fpm): si no, la .sqlite que crea root queda 644 y fpm no puede escribir
HTTP_PORT ?= 9100
CURRENT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
# ramas que sincroniza update-branch (este repo solo tiene main): make update-branch BRANCHES="main dev"
BRANCHES ?= main

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

env: ## crea .env y docker/.env desde sus .example (no pisa los existentes) · make env
	@test -f .env || cp .env.example .env
	@test -f docker/.env || cp docker/.env.example docker/.env

start: env up ## primer arranque: env + contenedores (vendor y migraciones van en el arranque) · make start
	@echo "http://localhost:$(HTTP_PORT)"

up: ## levanta los contenedores (+ composer install y migraciones) · make up
	$(DC) up -d
	docker ps --filter name=task-php

down: ## para y elimina los contenedores · make down
	$(DC) down

restart: ## reinicia los contenedores (vuelve a pasar por composer install y migraciones) · make restart
	$(DC) restart

rebuild: ## reconstruye las imagenes y relanza · make rebuild
	$(DC) down
	$(DC) up -d --build --remove-orphans

ps: ## contenedores del stack · make ps
	docker ps --filter name=task-php

logs: ## logs de los contenedores (follow) · make logs
	$(DC) logs -f

config: ## compose resuelto · make config
	$(DC) config

ssh-fpm: ## shell en php-fpm · make ssh-fpm
	docker exec -it $(FPM) sh

ssh-nginx: ## shell en nginx · make ssh-nginx
	docker exec -it cont-task-php-nginx sh

composer-install: ## composer install a mano dentro de php-fpm · make composer-install
	docker exec $(FPM) composer install --no-interaction

console: ## comando de consola · make console c="help"
	docker exec -it -u www-data -w /app/console $(FPM) php console.php $(c)

migrate: ## migraciones .sql pendientes a mano (database/migrations) · make migrate
	docker exec -u www-data -w /app/console $(FPM) php console.php run-migrations

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
	git add .; git commit -m "php: $(m)"; git push;

#!/bin/bash
# Makefile de la raiz del monorepo: atajos para levantar/parar todos los backs y los fronts (cada uno con su make).
# Lanzar desde la raiz de prj-task-cruds/. make help lista los targets.
# docker con sudo (vnic-eaf-001): make up DOCKER="sudo docker" -> hoy solo lo respeta task-python (los demas llaman a "docker").
DOCKER ?= docker

# nombre -> carpeta task-<nombre> · puertos en readme.md
BACKS  := php go rust nodejs java python
FRONTS := js webassembly
ALL    := $(BACKS) $(FRONTS)

# seleccion opcional: make up b="python go" f="js"  (b = backs, f = fronts; vacio = todos)
SEL_BACKS  := $(if $(b),$(b),$(BACKS))
SEL_FRONTS := $(if $(f),$(f),$(FRONTS))

# targets que no son ficheros (existen carpetas task-*)
.PHONY: help up up-backs up-fronts down down-backs down-fronts ps health gitpush gp $(addprefix up-,$(ALL)) $(addprefix down-,$(ALL))

help: ## Show this help message · make help
	@echo "usage: make [target]"
	@echo
	@echo "targets:"
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-14s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo
	@echo "por proyecto: make up-<nombre> / down-<nombre> con nombre = $(ALL)"

up: up-backs up-fronts ## levanta todos los backs y los fronts (env + contenedores) · make up  |  make up b="python" f="js"

up-backs: ## levanta los backs (php go rust nodejs java python) · make up-backs  |  make up-backs b="python go"
	@for p in $(SEL_BACKS); do echo "== task-$$p"; $(MAKE) --no-print-directory -C task-$$p start DOCKER="$(DOCKER)" || exit 1; done

up-fronts: ## levanta los fronts (js :9200, webassembly :9400) · make up-fronts  |  make up-fronts f="js"
	@for p in $(SEL_FRONTS); do echo "== task-$$p"; $(MAKE) --no-print-directory -C task-$$p start DOCKER="$(DOCKER)" || exit 1; done

down: down-fronts down-backs ## para todo (fronts y backs) · make down

down-backs: ## para los backs · make down-backs  |  make down-backs b="python"
	@for p in $(SEL_BACKS); do echo "== task-$$p"; $(MAKE) --no-print-directory -C task-$$p down DOCKER="$(DOCKER)"; done

down-fronts: ## para los fronts · make down-fronts  |  make down-fronts f="js"
	@for p in $(SEL_FRONTS); do echo "== task-$$p"; $(MAKE) --no-print-directory -C task-$$p down DOCKER="$(DOCKER)"; done

# make up-python, make down-js, ...
$(addprefix up-,$(ALL)): up-%:
	$(MAKE) --no-print-directory -C task-$* start DOCKER="$(DOCKER)"

$(addprefix down-,$(ALL)): down-%:
	$(MAKE) --no-print-directory -C task-$* down DOCKER="$(DOCKER)"

ps: ## contenedores de todos los proyectos (task-*) · make ps
	$(DOCKER) ps -a --filter name=cont-task- --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

health: ## health de cada back (9100 php · 9300 go · 9500 rust · 9600 nodejs · 9700 java · 9800 python) y de los fronts · make health
	@for e in php:9100 go:9300 rust:9500 nodejs:9600 java:9700 python:9800; do \
		printf "%-12s" $${e%%:*}; curl -s -m 3 -o /dev/null -w "%{http_code}\n" http://localhost:$${e##*:}/health || echo "down"; done
	@for e in js:9200 webassembly:9400; do \
		printf "%-12s" $${e%%:*}; curl -s -m 3 -o /dev/null -w "%{http_code}\n" http://localhost:$${e##*:}/ || echo "down"; done

gitpush: ## git push m=any message
	clear;
	git pull;
	git add .; git commit -m "$(m)"; git push;

gp: ## git push sin pull previo m=any message
	clear;
	git add .; git commit -m "$(m)"; git push;

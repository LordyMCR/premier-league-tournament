# Docker shortcuts for local dev and production (VPS)
# Requires: Docker, GNU Make (Git Bash / WSL / choco install make on Windows)
#
# Usage: make help

COMPOSE_DEV  := docker compose
COMPOSE_LIVE := docker compose -f docker-compose.live.yml

.DEFAULT_GOAL := help

.PHONY: help \
	start-dev stop-dev restart-dev rebuild rebuild-fresh ps logs logs-app migrate shell artisan cache-clear install-dev \
	start-live stop-live restart-live rebuild-live ps-live logs-live logs-web logs-caddy migrate-live shell-live artisan-live deploy-live

help: ## Show available commands
	@echo ""
	@echo "  Local development (docker-compose.yml)"
	@echo "  --------------------------------------"
	@echo "  make start-dev       Start containers"
	@echo "  make stop-dev        Stop containers"
	@echo "  make restart-dev     Restart dev containers"
	@echo "  make rebuild         Rebuild images and start"
	@echo "  make rebuild-fresh   Wipe dev volumes + rebuild"
	@echo "  make ps              Container status"
	@echo "  make logs            Follow all logs"
	@echo "  make logs-app        Follow app (PHP) logs"
	@echo "  make migrate         Run migrations"
	@echo "  make shell           Bash in app container"
	@echo "  make artisan CMD=\"...\"  Run artisan"
	@echo "  make cache-clear     Clear Laravel caches"
	@echo "  make install-dev     composer install in container"
	@echo ""
	@echo "  http://localhost:8080  |  Vite http://localhost:5173"
	@echo ""
	@echo "  Production on VPS (docker-compose.live.yml)"
	@echo "  -------------------------------------------"
	@echo "  make start-live      Start production stack"
	@echo "  make stop-live       Stop production stack"
	@echo "  make restart-live    Restart production containers"
	@echo "  make rebuild-live    Rebuild images and start"
	@echo "  make deploy-live     git pull + rebuild (same as GitHub Actions)"
	@echo "  make ps-live         Container status"
	@echo "  make logs-live       Follow all logs"
	@echo "  make logs-web        Follow web (PHP) logs"
	@echo "  make logs-caddy      Follow Caddy / SSL logs"
	@echo "  make migrate-live    Run migrations (--force)"
	@echo "  make shell-live      Bash in web container"
	@echo "  make artisan-live CMD=\"...\"  Run artisan on production"
	@echo ""

# --- Local development ---

start-dev:
	$(COMPOSE_DEV) up -d

stop-dev:
	$(COMPOSE_DEV) down

restart-dev:
	$(COMPOSE_DEV) restart

restart: restart-dev

rebuild:
	$(COMPOSE_DEV) up -d --build

rebuild-fresh:
	$(COMPOSE_DEV) down -v
	$(COMPOSE_DEV) up -d --build

ps:
	$(COMPOSE_DEV) ps

logs:
	$(COMPOSE_DEV) logs -f

logs-app:
	$(COMPOSE_DEV) logs -f app

migrate:
	$(COMPOSE_DEV) exec app php artisan migrate

shell:
	$(COMPOSE_DEV) exec app bash

artisan:
	$(COMPOSE_DEV) exec app php artisan $(CMD)

cache-clear:
	$(COMPOSE_DEV) exec app php artisan optimize:clear

install-dev:
	$(COMPOSE_DEV) exec app composer install

# --- Production (SSH into VPS, cd /var/www/premier-league-tournament) ---

start-live:
	$(COMPOSE_LIVE) up -d

stop-live:
	$(COMPOSE_LIVE) down

restart-live:
	$(COMPOSE_LIVE) restart

rebuild-live:
	$(COMPOSE_LIVE) up -d --build

deploy-live:
	bash scripts/deploy-live.sh

ps-live:
	$(COMPOSE_LIVE) ps

logs-live:
	$(COMPOSE_LIVE) logs -f

logs-web:
	$(COMPOSE_LIVE) logs -f web

logs-caddy:
	$(COMPOSE_LIVE) logs -f caddy

migrate-live:
	$(COMPOSE_LIVE) exec web php artisan migrate --force

shell-live:
	$(COMPOSE_LIVE) exec web bash

artisan-live:
	$(COMPOSE_LIVE) exec web php artisan $(CMD)

SHELL := /bin/sh
.DEFAULT_GOAL := help

.PHONY: help setup migrate migrate-status seed demo dev serve queue test format build check routes

help:
	@printf '%s\n' \
	  'FarmaCore developer commands:' \
	  '  make setup          Install dependencies and prepare local .env/app key' \
	  '  make migrate        Run pending database migrations' \
	  '  make seed           Seed baseline roles/settings/master data' \
	  '  make demo           Seed fictitious demo users and business data' \
	  '  make dev            Start the configured Laravel development processes' \
	  '  make serve          Start only the Laravel HTTP server' \
	  '  make queue          Start the database queue worker' \
	  '  make test           Run the Pest suite (FILTER=name for a focused test)' \
	  '  make format         Format changed PHP files with Pint' \
	  '  make build          Build frontend assets' \
	  '  make check          Run tests, formatting, and frontend build' \
	  '  make routes         List application routes'

setup:
	@test -f .env || cp .env.example .env
	composer install --no-interaction
	npm ci
	@if ! grep -Eq '^APP_KEY=.+$$' .env; then \
		php artisan key:generate --no-interaction; \
	else \
		printf '%s\n' 'APP_KEY already exists; leaving it unchanged.'; \
	fi

migrate:
	php artisan migrate --no-interaction

migrate-status:
	php artisan migrate:status --no-interaction

seed:
	php artisan db:seed --no-interaction

demo: migrate
	php artisan db:seed --class=DemoDataSeeder --no-interaction

dev:
	composer run dev

serve:
	php artisan serve

queue:
	php artisan queue:work

test:
	@if [ -n "$(FILTER)" ]; then \
		php artisan test --compact --filter="$(FILTER)"; \
	else \
		php artisan test --compact; \
	fi

format:
	vendor/bin/pint --dirty --format agent

build:
	npm run build

check:
	$(MAKE) test
	$(MAKE) format
	$(MAKE) build

routes:
	php artisan route:list --except-vendor

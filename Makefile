# Canonical command surface for humans, coding agents and CI.
# PHP and Composer run only inside containers; nothing here requires PHP on the host.
# Run `make help` for a list of targets.

COMPOSE        ?= docker compose
SERVER_DIR     := server
COMPOSER_IMAGE ?= composer:2
NODE_IMAGE     ?= node:24
OVERRIDE       := compose.override.yaml
OVERRIDE_EX    := compose.override.yaml.example
DATA_DIR       := data

# Commands that run inside the running `web` service. Apache runs PHP as www-data,
# so artisan must run as www-data too; otherwise root-owned compiled views, caches
# and logs make the web process fail with "tempnam(): file created in the system's
# temporary directory" (HTTP 500). Use EXEC_ROOT only for container housekeeping.
APP_USER  ?= www-data
EXEC      := $(COMPOSE) exec -u $(APP_USER) web
EXEC_ROOT := $(COMPOSE) exec web

# Allocate a TTY only when we have one (CI and background agents do not).
TTY := $(shell [ -t 0 ] && echo "-it" || echo "-i")

# Ephemeral containers for Composer and npm, mounting ./server. Used so that
# dev dependencies (needed for tests) exist under the bind mount even though the
# image itself is built with --no-dev.
COMPOSER_RUN := docker run --rm $(TTY) -v "$(CURDIR)/$(SERVER_DIR):/app" -w /app $(COMPOSER_IMAGE)
NODE_RUN     := docker run --rm $(TTY) -v "$(CURDIR)/$(SERVER_DIR):/app" -w /app $(NODE_IMAGE)

.DEFAULT_GOAL := help
.PHONY: help init setup up down restart logs ps shell shell-root artisan composer npm build dev test test-db lint pint stan stan-baseline \
        migrate db-shell db-import db-dump db-reset db-restore schema-export lexicon-cache upstream-fetch secret-scan check-docker

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
	  awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

check-docker:
	@docker info >/dev/null 2>&1 || { echo "Docker is not running. Start Docker Desktop and retry."; exit 1; }

## ---- Setup ---------------------------------------------------------------

init: check-docker $(OVERRIDE) ## First-time setup: local config, composer and npm deps, image build
	@mkdir -p $(DATA_DIR)
	$(COMPOSER_RUN) composer install --ignore-platform-reqs --no-interaction
	$(NODE_RUN) npm ci
	$(COMPOSE) build
	@echo
	@echo "Done. Next: make up, then see docs/runbooks/local-development.md"

setup: init up migrate ## Alias: init, up and migrate in one step

$(OVERRIDE): ## Create compose.override.yaml with a fresh APP_KEY (never committed)
	@if [ -f $(OVERRIDE) ]; then exit 0; fi
	@KEY="base64:$$(openssl rand -base64 32)"; \
	  sed "s|APP_KEY=base64:REPLACE_ME|APP_KEY=$$KEY|" $(OVERRIDE_EX) > $(OVERRIDE); \
	  echo "Created $(OVERRIDE) with a generated APP_KEY."

## ---- Stack ---------------------------------------------------------------

up: check-docker ## Start web and db in the background
	$(COMPOSE) up -d
	@# Compiled views live at VIEW_COMPILED_PATH=/tmp/laravel (compose.yaml); make sure www-data owns it.
	@$(EXEC_ROOT) sh -c 'mkdir -p /tmp/laravel && chown $(APP_USER) /tmp/laravel'
	@echo "web is on http://localhost:$$($(COMPOSE) port web 80 | cut -d: -f2) (or via your proxy at VIRTUAL_HOST)"

down: ## Stop the stack (keeps the database volume)
	$(COMPOSE) down

restart: down up ## Restart the stack

logs: ## Follow container logs
	$(COMPOSE) logs -f --tail=100

ps: ## Show container status
	$(COMPOSE) ps

shell: ## Open a shell in the web container as the app user (www-data)
	$(EXEC) sh -c 'command -v bash >/dev/null && exec bash || exec sh'

shell-root: ## Open a root shell in the web container (housekeeping only)
	$(EXEC_ROOT) sh -c 'command -v bash >/dev/null && exec bash || exec sh'

## ---- App commands --------------------------------------------------------

artisan: ## Run artisan: make artisan ARGS="migrate"
	$(EXEC) php artisan $(ARGS)

composer: ## Run composer against ./server: make composer ARGS="require foo/bar"
	$(COMPOSER_RUN) composer $(ARGS) --ignore-platform-reqs

npm: ## Run npm against ./server: make npm ARGS="run build"
	$(NODE_RUN) npm $(ARGS)

build: ## Build frontend assets once (Vite)
	$(NODE_RUN) npm run build

dev: ## Run the Vite dev server (foreground)
	docker run --rm $(TTY) -v "$(CURDIR)/$(SERVER_DIR):/app" -w /app -p 5173:5173 $(NODE_IMAGE) npm run dev -- --host

migrate: ## Run pending migrations
	$(EXEC) php artisan migrate

## ---- Quality -------------------------------------------------------------

TEST_DB ?= lrc_test

test-db: ## Create the separate test database (lrc_test) so tests never touch the dev database
	@$(COMPOSE) exec -T db mariadb -uroot -plrc -e "CREATE DATABASE IF NOT EXISTS \`$(TEST_DB)\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`$(TEST_DB)\`.* TO 'lrc'@'%'; FLUSH PRIVILEGES;"

test: test-db ## Run the test suite against the lrc_test database: make test ARGS="--filter Foo"
	@# An empty .env silences a vendor solution-provider warning; real config comes from compose env vars.
	@test -f $(SERVER_DIR)/.env || touch $(SERVER_DIR)/.env
	$(COMPOSE) exec -u $(APP_USER) -e DB_DATABASE=$(TEST_DB) web php artisan test $(ARGS)

lint: ## Style check plus static analysis on the whole tree (what CI runs): make lint
	$(MAKE) pint ARGS="--test"
	$(MAKE) stan

pint: ## Run Laravel Pint (dev dependency): make pint ARGS="--test app/Models/Foo.php" (changed files only, see AGENTS.md §4)
	$(EXEC) vendor/bin/pint $(ARGS)

stan: ## Run Larastan/PHPStan with the committed baseline
	$(EXEC) vendor/bin/phpstan analyse --memory-limit=1G $(ARGS)

stan-baseline: ## Regenerate phpstan-baseline.neon (only in the PR that raises the level or after a sync)
	$(EXEC) vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline phpstan-baseline.neon --allow-empty-baseline

secret-scan: ## Scan the working tree and full git history for secrets (same rules as CI)
	docker run --rm -v "$(CURDIR):/repo:ro" zricethezav/gitleaks:latest detect --source=/repo --config=/repo/.gitleaks.toml --log-opts="--all" --no-banner --redact

## ---- Database ------------------------------------------------------------

db-shell: ## Open a MariaDB client on the local database
	$(COMPOSE) exec db mariadb -ulrc -plrc lrc

db-import: ## Import a SQL dump into the local database: make db-import FILE=data/dump.sql
	@test -n "$(FILE)" || { echo "Usage: make db-import FILE=data/dump.sql"; exit 1; }
	@test -f "$(FILE)" || { echo "File not found: $(FILE)"; exit 1; }
	$(COMPOSE) exec -T db mariadb -ulrc -plrc lrc < "$(FILE)"
	@echo "Imported $(FILE). Now run: make migrate"

db-dump: ## Dump the local database to data/local-<timestamp>.sql
	@mkdir -p $(DATA_DIR)
	$(COMPOSE) exec -T db mariadb-dump -ulrc -plrc lrc > $(DATA_DIR)/local-$$(date +%Y%m%d-%H%M%S).sql
	@ls -t $(DATA_DIR)/local-*.sql | head -1

db-reset: ## Drop everything, migrate from scratch and seed the local database (local only; asks first)
	@printf "This wipes the LOCAL database in the db container. Continue? [y/N] " && read a && [ "$$a" = "y" ] || { echo "aborted"; exit 1; }
	$(EXEC) php artisan migrate:fresh --seed

db-restore: ## Download a versioned dump and import it: make db-restore DUMP_URL=https://... (or FILE=data/x.sql)
	@test -n "$(DUMP_URL)$(FILE)" || { echo "Usage: make db-restore DUMP_URL=<url> | FILE=<path>"; exit 1; }
	@mkdir -p $(DATA_DIR)
	@if [ -n "$(DUMP_URL)" ]; then curl -fsSL "$(DUMP_URL)" -o $(DATA_DIR)/restore.sql && $(MAKE) db-import FILE=$(DATA_DIR)/restore.sql; else $(MAKE) db-import FILE="$(FILE)"; fi
	$(MAKE) migrate

schema-export: ## Export the current schema DDL to docs/data-model/current-schema.sql
	$(COMPOSE) exec -T db mariadb-dump -ulrc -plrc --no-data --skip-comments --skip-add-drop-table lrc 2>/dev/null \
	  | sed -E 's/ AUTO_INCREMENT=[0-9]+//' > docs/data-model/current-schema.sql
	@grep -c "CREATE TABLE" docs/data-model/current-schema.sql | xargs -I{} echo "{} tables written to docs/data-model/current-schema.sql"

lexicon-cache: ## Rebuild the public data cache for one lexicon: make lexicon-cache ID=1
	@test -n "$(ID)" || { echo "Usage: make lexicon-cache ID=<lexicon_id>"; exit 1; }
	$(EXEC) php artisan app:generate-lexicon-data-cache $(ID)

## ---- Upstream ------------------------------------------------------------

upstream-fetch: ## Fetch cola-laits and fast-forward the local `upstream` branch (see docs/runbooks/syncing-upstream.md)
	git fetch upstream
	git fetch . upstream/master:upstream 2>/dev/null || git branch -f upstream upstream/master
	@echo "upstream branch is at $$(git rev-parse --short upstream). master is $$(git rev-list --count upstream..master) ahead, $$(git rev-list --count master..upstream) behind."

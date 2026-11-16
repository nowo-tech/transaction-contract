# Makefile for TransactionContract (nowo-tech/transaction-contract)
# REQ-MAKE-001 / REQ-MAKE-002 / REQ-MAKE-006 / REQ-MAKE-008 / REQ-MAKE-010

.PHONY: help ensure-up up down down-dev build shell install test test-coverage test-coverage-100 cs-check cs-fix rector rector-dry phpstan igor qa release-check composer-sync clean update update-deps validate setup-hooks check-no-cursor-coauthor strip-cursor-coauthor-from-history check-open-prs

COMPOSER_BIN := /usr/bin/composer
COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

help:
	@echo "Transaction Contract — development commands"
	@echo ""
	@echo "  up / down / down-dev   Start / stop the PHP dev container"
	@echo "  shell                  Shell in the container"
	@echo "  install / update       Composer install / update"
	@echo "  test                   PHPUnit"
	@echo "  test-coverage          PHPUnit with coverage + global PHP percentage"
	@echo "  test-coverage-100      Fail below 100% line coverage"
	@echo "  cs-check / cs-fix      PHP-CS-Fixer"
	@echo "  rector / rector-dry    Rector"
	@echo "  phpstan                PHPStan (level 8 + FrankenPHP rulesets)"
	@echo "  igor                   Igor worker-state audit (REQ-CS-008)"
	@echo "  qa                     cs-check + phpstan + test"
	@echo "  release-check          Full pre-release chain (REQ-MAKE-002)"
	@echo "  setup-hooks            Install git hooks (REQ-GIT-001)"

ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		$(COMPOSE) up -d --build; \
		sleep 3; \
		$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction; \
	fi

build:
	$(COMPOSE) build

up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@sleep 2
	$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction
	@echo "Container ready"

down:
	$(COMPOSE) down

down-dev:
	$(COMPOSE) down --remove-orphans

shell: ensure-up
	$(COMPOSE) exec php sh

install: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction

test: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) test

test-coverage: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) test-coverage | tee coverage-php.txt
	sh ./.scripts/php-coverage-percent.sh coverage-php.txt

test-coverage-100: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) test-coverage-100

cs-check: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) cs-check

cs-fix: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) cs-fix

rector: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) rector

rector-dry: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) rector-dry

phpstan: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) phpstan

igor: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) igor

qa: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) qa

composer-sync: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) validate --strict
	$(COMPOSE) exec -T php $(COMPOSER_BIN) update --lock --no-interaction --no-install

check-open-prs:
	@bash .scripts/check-open-prs.sh

# REQ-MAKE-002 order (plain library: no Twig, no demo).
release-check:
	@$(MAKE) ensure-up
	@$(MAKE) check-no-cursor-coauthor
	@$(MAKE) check-open-prs
	@$(MAKE) composer-sync
	@$(MAKE) cs-fix
	@$(MAKE) cs-check
	@$(MAKE) rector-dry
	@$(MAKE) phpstan
	@$(MAKE) igor
	@$(MAKE) test-coverage
	@$(MAKE) test-coverage-100

update: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) update --no-interaction

validate: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) validate --strict

clean:
	rm -rf vendor .phpunit.cache coverage coverage.xml coverage-php.txt .php-cs-fixer.cache

check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh .scripts/check-no-claude-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

setup-hooks:
	@chmod +x .githooks/pre-commit .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "Git hooks installed (.githooks — includes commit-msg for REQ-GIT-001)."

# REQ-MAKE-008: update-deps (soft include of the workspace helper when present)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk
ifeq ($(wildcard $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk),)
update-deps: update
endif

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main

# Makefile for TYPO3 Extension nr_saml_auth
# Self-documenting: run 'make' or 'make help' to see available targets

.DEFAULT_GOAL := help
.PHONY: help install test lint fix quality ci clean docs up start stop restart install-v12 install-v13 install-all urls ssh

# Colors for output
BLUE := \033[34m
GREEN := \033[32m
YELLOW := \033[33m
RESET := \033[0m

##@ Development

install: ## Install all dependencies
	@echo "$(BLUE)Installing Composer dependencies...$(RESET)"
	@composer install

update: ## Update all dependencies
	@echo "$(BLUE)Updating Composer dependencies...$(RESET)"
	@composer update

##@ Testing

test: test-unit test-functional ## Run all tests

test-unit: ## Run unit tests
	@echo "$(BLUE)Running unit tests...$(RESET)"
	@composer ci:tests:unit

test-functional: ## Run functional tests
	@echo "$(BLUE)Running functional tests...$(RESET)"
	@composer ci:tests:functional

##@ Code Quality

lint: ## Check code style (dry-run)
	@echo "$(BLUE)Checking code style...$(RESET)"
	@composer ci:cgl

fix: ## Fix code style issues
	@echo "$(BLUE)Fixing code style...$(RESET)"
	@composer ci:cgl:fix

phpstan: ## Run PHPStan static analysis
	@echo "$(BLUE)Running PHPStan...$(RESET)"
	@composer ci:phpstan

phpstan-baseline: ## Generate PHPStan baseline
	@echo "$(BLUE)Generating PHPStan baseline...$(RESET)"
	@composer ci:phpstan:baseline

quality: lint phpstan ## Run all code quality checks

##@ CI/CD

ci: ## Run full CI pipeline (lint + phpstan + tests)
	@echo "$(GREEN)Running full CI pipeline...$(RESET)"
	@composer ci

##@ Documentation

docs: ## Render documentation locally (requires Docker)
	@echo "$(BLUE)Rendering documentation...$(RESET)"
	@docker run --rm -v $(PWD):/project -t ghcr.io/typo3-documentation/render-guides:latest --config Documentation

docs-serve: ## Serve documentation locally
	@echo "$(BLUE)Serving documentation at http://localhost:8000...$(RESET)"
	@cd Documentation-GENERATED-temp && python3 -m http.server 8000

##@ DDEV Environment

up: ## Complete startup: start DDEV + install all TYPO3 versions
	@echo "$(GREEN)Starting DDEV and installing TYPO3...$(RESET)"
	@ddev start
	@ddev install-typo3 all
	@echo ""
	@echo "$(GREEN)Environment ready!$(RESET)"
	@$(MAKE) urls

start: ## Start DDEV environment
	@ddev start

stop: ## Stop DDEV environment
	@ddev stop

restart: ## Restart DDEV environment
	@ddev restart

install-v12: ## Install/reinstall TYPO3 12.4 LTS
	@ddev install-typo3 12

install-v13: ## Install/reinstall TYPO3 13.4 LTS
	@ddev install-typo3 13

install-all: ## Install/reinstall all TYPO3 versions
	@ddev install-typo3 all

urls: ## Show all access URLs
	@echo ""
	@echo "$(GREEN)Access URLs:$(RESET)"
	@echo "  $(BLUE)Overview:$(RESET)  https://nr-saml-auth.ddev.site/"
	@echo "  $(BLUE)TYPO3 12:$(RESET)  https://v12.nr-saml-auth.ddev.site/typo3/"
	@echo "  $(BLUE)TYPO3 13:$(RESET)  https://v13.nr-saml-auth.ddev.site/typo3/"
	@echo "  $(BLUE)Docs:$(RESET)      https://docs.nr-saml-auth.ddev.site/"
	@echo ""
	@echo "  $(YELLOW)Credentials:$(RESET) admin / Joh316!"
	@echo ""

ssh: ## SSH into DDEV web container
	@ddev ssh

##@ Cleanup

clean: ## Clean build artifacts
	@echo "$(YELLOW)Cleaning build artifacts...$(RESET)"
	@rm -rf .Build/cache
	@rm -rf .Build/log
	@rm -rf .php-cs-fixer.cache
	@rm -rf Documentation-GENERATED-temp
	@echo "$(GREEN)Clean complete.$(RESET)"

clean-all: clean ## Clean everything including vendor
	@echo "$(YELLOW)Removing vendor directories...$(RESET)"
	@rm -rf .Build/vendor
	@rm -rf .Build/bin
	@echo "$(GREEN)Full clean complete.$(RESET)"

##@ Help

help: ## Display this help message
	@echo ""
	@echo "$(GREEN)TYPO3 Extension: nr_saml_auth$(RESET)"
	@echo "$(BLUE)Available targets:$(RESET)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf ""} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2 } /^##@/ { printf "\n$(YELLOW)%s$(RESET)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
	@echo ""

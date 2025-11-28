# Makefile for TYPO3 Extension nr_saml_auth
# Self-documenting: run 'make' or 'make help' to see available targets

.DEFAULT_GOAL := help
.PHONY: help install test lint fix quality ci clean docs

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

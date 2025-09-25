SHELL := /bin/bash

# Default target
.PHONY: help
help:
	@echo "Usage:"
	@echo "  make install       # composer install"
	@echo "  make update        # composer update"
	@echo "  make test          # run tests"
	@echo "  make coverage      # run tests with coverage"
	@echo "  make lint          # run phpcs"
	@echo "  make fix           # run phpcbf (auto-fix)"
	@echo "  make stan          # run phpstan"
	@echo "  make qa            # validate + lint + stan + test"

.PHONY: install
install:
	composer install --no-interaction --prefer-dist --no-progress

.PHONY: update
update:
	composer update --no-interaction --prefer-dist --no-progress

.PHONY: test
test:
	composer run test

.PHONY: coverage
coverage:
	composer run test:coverage

.PHONY: lint
lint:
	composer run lint

.PHONY: fix
fix:
	composer run lint:fix

.PHONY: stan
stan:
	composer run stan

.PHONY: qa
qa:
	composer run qa
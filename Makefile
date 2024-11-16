.PHONY: all coverage coverage-js coverage-php doc doc-js doc-php increment sign test test-js test-php help

CURRENT_VERSION = 1.7.5
VERSION ?= 1.7.6
VERSION_FILES = README.md SECURITY.md doc/Installation.md js/package*.json lib/Controller.php Makefile
REGEX_CURRENT_VERSION := $(shell echo $(CURRENT_VERSION) | sed "s/\./\\\./g")
REGEX_VERSION := $(shell echo $(VERSION) | sed "s/\./\\\./g")

all: coverage doc ## Equivalent to running `make coverage doc`.

composer: ## Update composer dependencies (only production ones, optimize the autoloader)
	composer update --no-dev --optimize-autoloader

coverage: coverage-js coverage-php ## Run all unit tests and generate code coverage reports.

coverage-js: ## Run JS unit tests and generate code coverage reports.
	cd js && nyc mocha

coverage-php: ## Run PHP unit tests and generate code coverage reports.
	cd tst && XDEBUG_MODE=coverage phpunit 2> /dev/null
	cd tst/log/php-coverage-report && sed -i "s#$(CURDIR)/##g" *.html */*.html

doc: doc-js doc-php ## Generate all code documentation.

doc-js: ## Generate JS code documentation.
	jsdoc -p -d doc/jsdoc js/privatebin.js js/legacy.js

doc-php: ## Generate JS code documentation.
	phpdoc --visibility=public,protected,private --target=doc/phpdoc --directory=lib/

increment: ## Increment and commit new version number, set target version using `make increment VERSION=1.2.3`.
	for F in `grep -l -R $(REGEX_CURRENT_VERSION) $(VERSION_FILES)`; \
	do \
		sed -i "s/$(REGEX_CURRENT_VERSION)/$(REGEX_VERSION)/g" $$F; \
	done
	git add $(VERSION_FILES) CHANGELOG.md
	git commit -m "incrementing version"

sign: ## Sign a release.
	git tag --sign --message "Release v$(VERSION)" $(VERSION)
	git push origin $(VERSION)
	signrelease.sh

test: test-js test-php ## Run all unit tests.

test-js: ## Run JS unit tests.
	cd js && mocha

test-php: ## Run PHP unit tests.
	cd tst && phpunit --no-coverage

help: ## Displays these usage instructions.
	@echo "Usage: make <target(s)>"
	@echo
	@echo "Specify one or multiple of the following targets and they will be processed in the given order:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "%-16s%s\n", $$1, $$2}' $(MAKEFILE_LIST)

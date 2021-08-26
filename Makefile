SHELL=bash
SOURCE_DIR = $(shell pwd)
BIN_DIR ?= ${SOURCE_DIR}/bin
COMPOSER_DIR = ${SOURCE_DIR}/composer
COMPOSER_BIN := $(shell type -P composer)

ifndef COMPOSER_BIN
    COMPOSER_BIN = ${COMPOSER_DIR}/composer.phar
endif

define printSection
    @printf "\033[36m\n==================================================\n\033[0m"
    @printf "\033[36m $1 \033[0m"
    @printf "\033[36m\n==================================================\n\033[0m"
endef

# BUILD
.PHONY: build
build: install test quality sf-security-checker

# INSTALL
.PHONY: install
install: clean-vendor clean-bin clean-composer composer-install

#CODE STYLE
.PHONY: quality
quality: cs-ci phpstan

# CLEAN for various directories used by Makefile.
.PHONY: clean-vendor
clean-vendor:
	$(call printSection,CLEAN-VENDOR)
	rm -rf ${SOURCE_DIR}/vendor

.PHONY: clean-bin
clean-bin:
	$(call printSection,CLEAN-BIN)
	rm -rf ${SOURCE_DIR}/bin

.PHONY: clean-composer
clean-composer:
	$(call printSection,CLEAN-COMPOSER)
	rm -f ${SOURCE_DIR}/composer.install.log
	rm -f ${SOURCE_DIR}/composer.lock

# COMPOSER
${COMPOSER_BIN}:
	$(call printSection,COMPOSER-CLONE)
	git clone https://github.m6web.fr/m6web/tool-composer.git ${COMPOSER_DIR}
	COMPOSER_HOME=${COMPOSER_DIR}/composer-home
	chmod 755 ${COMPOSER_BIN}
	[ -L ${SOURCE_DIR}/composer.phar ] || ln -s ${COMPOSER_DIR}/composer.phar ${SOURCE_DIR}/composer.phar

.PHONY: composer-install
composer-install: ${SOURCE_DIR}/composer.install.log

${SOURCE_DIR}/composer.install.log: ${COMPOSER_BIN}
	$(call printSection,COMPOSER-INSTALL)
	$< --no-interaction install --ansi --no-progress --prefer-dist 2>&1 | tee ${SOURCE_DIR}/composer.install.log

# SECURITY CHECKER
.PHONY: sf-security-checker
sf-security-checker:
	$(call printSection,COMPOSER-SECURITY-CHECKER)
	${SOURCE_DIR}/.ci/security-checker.sh

# TEST
.PHONY: test
test: phpunit

.PHONY: phpunit
phpunit:
	$(call printSection,PHPUNIT)
	${BIN_DIR}/simple-phpunit

vendor/bin/.phpunit: phpunit

.PHONY: phpstan
phpstan: vendor/bin/.phpunit
	${BIN_DIR}/phpstan.phar analyse

# QUALITY
.PHONY: cs
cs:
	$(call printSection,CS)
	${BIN_DIR}/php-cs-fixer fix --ansi --dry-run --stop-on-violation --diff

.PHONY: cs-fix
cs-fix:
	$(call printSection,CS-fix)
	${BIN_DIR}/php-cs-fixer fix --ansi

.PHONY: cs-ci
cs-ci:
	$(call printSection,CS-CI)
	${BIN_DIR}/php-cs-fixer fix --ansi --dry-run --using-cache=no --verbose

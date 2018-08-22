SHELL=bash
SOURCE_DIR = $(shell pwd)
BASE_DIR = ${SOURCE_DIR}/..
BIN_DIR ?= ${SOURCE_DIR}/bin
LOGS_DIR = ${BASE_DIR}/build/logs
COMPOSER_DIR = ${SOURCE_DIR}/composer
COMPOSER_BIN := $(shell type -P composer)

ifndef COMPOSER_BIN
    COMPOSER_BIN = ${COMPOSER_DIR}/composer.phar
endif

test: cs-ci phpunit

define printSection
    @printf "\033[36m\n==================================================\n\033[0m"
    @printf "\033[36m $1 \033[0m"
    @printf "\033[36m\n==================================================\n\033[0m"
endef

# TEST
phpunit:
	$(call printSection,PHPUNIT)
	${BIN_DIR}/simple-phpunit

phpunit-cc:
	$(call printSection,PHPUNIT)
	${BIN_DIR}/simple-phpunit --coverage-text

# QUALITY
cs:
	$(call printSection,CS)
	${BIN_DIR}/php-cs-fixer fix --ansi --dry-run --stop-on-violation --diff

cs-fix:
	$(call printSection,CS-fix)
	${BIN_DIR}/php-cs-fixer fix --ansi

cs-ci:
	$(call printSection,CS-CI)
	${BIN_DIR}/php-cs-fixer fix --ansi --dry-run --using-cache=no --verbose

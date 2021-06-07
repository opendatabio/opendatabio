#PHP Laravel environment modified from https://github.com/dimadeush/docker-nginx-php-laravel
dir=${CURDIR}
export COMPOSE_PROJECT_NAME=odb

ifndef APP_ENV
	# Determine if .env file exist
	ifneq ("$(wildcard .env)","")
		include .env
	endif
endif

laravel_user=-u www-data
project=-p ${COMPOSE_PROJECT_NAME}
service=${COMPOSE_PROJECT_NAME}:latest
interactive:=$(shell [ -t 0 ] && echo 1)
ifneq ($(interactive),1)
	optionT=-T
endif
ifeq ($(GITLAB_CI),1)
	# Determine additional params for phpunit in order to generate coverage badge on GitLabCI side
	phpunitOptions=--coverage-text --colors=never
endif

build:
	@docker-compose -f docker-compose.yml build

start:
	@docker-compose -f docker-compose.yml $(project) up -d

stop:
	@docker-compose -f docker-compose.yml $(project) down

restart: stop start

ssh:
	@docker-compose $(project) exec $(optionT) $(laravel_user) laravel bash

ssh-nginx:
	@docker-compose $(project) exec nginx /bin/sh

ssh-supervisord:
	@docker-compose $(project) exec supervisord bash

ssh-mysql:
	@docker-compose $(project) exec mysql bash

exec:
	@docker-compose $(project) exec $(optionT) $(laravel_user) laravel $$cmd

exec-bash:
	@docker-compose $(project) exec $(optionT) $(laravel_user) laravel bash -c "$(cmd)"

exec-by-root:
	@docker-compose $(project) exec $(optionT) laravel $$cmd

composer-install:
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader"

composer-update:
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"

key-generate:
	@make exec cmd="php artisan key:generate"

info:
	@make exec cmd="php artisan --version"
	@make exec cmd="php artisan env"
	@make exec cmd="php --version"

logs:
	@docker logs -f ${COMPOSE_PROJECT_NAME}_laravel

logs-nginx:
	@docker logs -f ${COMPOSE_PROJECT_NAME}_nginx

logs-supervisord:
	@docker logs -f ${COMPOSE_PROJECT_NAME}_supervisord

logs-mysql:
	@docker logs -f ${COMPOSE_PROJECT_NAME}_mysql

drop-migrate:
	@make exec cmd="php artisan migrate:fresh"

migrate:
	@make exec cmd="php artisan migrate --force"

optimize:
	@make exec cmd="php artisan cache:clear"
	@make exec cmd="php artisan view:clear"
	@make exec cmd="php artisan config:clear"
	@make exec cmd="echo '' > storage/logs/laravel.log"
	@make exec cmd="echo '' > storage/logs/supervisor.log"

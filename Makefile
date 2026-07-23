.PHONY: up down build logs shell artisan seed

up:
	docker compose up -d --build

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f app nginx node

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(cmd)

seed:
	docker compose exec app php artisan db:seed --force

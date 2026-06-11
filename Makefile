.PHONY: up down downV upRebuild

up: ## Start containers
	docker compose up -d
	docker compose exec php sh -c "cd proassist && composer install && php bin/console doctrine:migrations:migrate --no-interaction"

upRebuild:
	docker compose up --build -d
	docker compose exec php sh -c "cd proassist && composer install && php bin/console doctrine:migrations:migrate --no-interaction"

down: ## Stop containers
	docker compose down

downV: ## Stop containers
	docker compose down -v
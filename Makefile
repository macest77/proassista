.PHONY: up down downV

up: ## Start containers
	docker compose up -d
	docker compose exec php sh -c "cd proassist && composer install"

down: ## Stop containers
	docker compose down

downV: ## Stop containers
	docker compose down -v
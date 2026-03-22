#.PHONY: up down migrate seed test lint

up:
	docker compose up -d
down:
	docker compose down
migrate:
	docker exec -i task4-db mysql -uroot -proot task4 > migrate.sql
seed:
	docker exec -i task4-db mysql -uroot -proot task4 > seed.sql
test:
	docker exec task4-app php -l /var/www/html/index.php
lint:
	docker exec task4-app find /var/www/html -name "*.php" -exec php -l {}\;
# test
build:
	docker-compose build;
	docker-compose up -d;
	docker-compose exec php-fpm bash;

boot:
	docker-compose up -d;
	docker-compose exec php-fpm bash;

reboot:
	docker-compose stop;
	docker-compose up -d;
	docker-compose exec php-fpm bash;

shell:
	docker-compose exec php-fpm bash;

shell-db:
	docker-compose exec mysql bash;

shutdown:
	docker-compose stop;
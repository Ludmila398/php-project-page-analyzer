PORT ?= 8000

#start:
#  PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

start:
	php -S localhost:8000 -t public
install:
	composer install

validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public

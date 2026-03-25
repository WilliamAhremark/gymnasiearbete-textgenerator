FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app

COPY web/ .

CMD ["sh", "-c", "php -S 0.0.0.0:8080"]
CMD ["php", "-S", "0.0.0.0:8080"]
COPY web/ /app

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]

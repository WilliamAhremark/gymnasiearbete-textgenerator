FROM php:8.2-cli

WORKDIR /app

COPY web/ .

CMD ["sh", "-c", "php -S 0.0.0.0:8080"]

FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

COPY web/ .

CMD php -S 0.0.0.0:\

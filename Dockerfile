FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_pgsql

COPY web/ /var/www/html/

ENV PORT=80
EXPOSE 80

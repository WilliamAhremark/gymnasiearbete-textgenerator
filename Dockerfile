FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_pgsql

COPY web/ /var/www/html/

RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

EXPOSE 80

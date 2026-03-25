FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# ? FIX MPM ERROR
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/web

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/000-default.conf

# ? Railway PORT FIX
RUN echo "Listen ${PORT}" > /etc/apache2/ports.conf
RUN sed -ri -e "s!:80!:${PORT}!g" \
    /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html

CMD apache2ctl -D FOREGROUND

FROM php:8.2-apache

# install postgres driver
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# enable rewrite
RUN a2enmod rewrite

# set document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html

# ⭐ RAILWAY PORT FIX (REN metod)
CMD ["bash", "-c", "echo Listen $PORT > /etc/apache2/ports.conf && \
sed -i \"s/:80/:$PORT/g\" /etc/apache2/sites-available/000-default.conf && \
apache2-foreground"]

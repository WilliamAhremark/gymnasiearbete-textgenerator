FROM php:8.2-apache

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache modules
RUN a2enmod rewrite headers deflate

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Production PHP settings
RUN echo "display_errors=Off" > /usr/local/etc/php/conf.d/prod.ini && \
    echo "log_errors=On" >> /usr/local/etc/php/conf.d/prod.ini

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
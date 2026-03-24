FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && docker-php-ext-enable pdo pdo_pgsql

# Enable Apache modules needed for production
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod deflate

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Production PHP configuration
RUN echo "display_errors = Off" > /usr/local/etc/php/conf.d/production.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini && \
    echo "error_log = /var/log/apache2/php-errors.log" >> /usr/local/etc/php/conf.d/production.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/production.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/production.ini

# Add security headers
RUN echo "Header always set X-Frame-Options 'SAMEORIGIN'" >> /etc/apache2/apache2.conf && \
    echo "Header always set X-Content-Type-Options 'nosniff'" >> /etc/apache2/apache2.conf && \
    echo "Header always set X-XSS-Protection '1; mode=block'" >> /etc/apache2/apache2.conf

COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

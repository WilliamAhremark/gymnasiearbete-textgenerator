FROM php:8.2-apache

RUN echo 'HELLO FROM DOCKER BUILD'

COPY web/ /var/www/html/

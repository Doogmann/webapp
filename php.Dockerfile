# php.Dockerfile
FROM docker.io/library/php:8.2-fpm-alpine

WORKDIR /var/www/html

# App-kod
COPY public/ /var/www/html/public/
COPY src/ /var/www/html/src/

# PHP-ini
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN chown -R www-data:www-data /var/www/html

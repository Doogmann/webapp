# --------------------------
# PHP-FPM stage
# --------------------------
FROM php:8.2-fpm AS php

WORKDIR /var/www/html
COPY public/ /var/www/html/public/

# Sätt rättigheter
RUN chown -R www-data:www-data /var/www/html

# --------------------------
# Nginx stage
# --------------------------
FROM nginx:1.25-alpine AS nginx

# Ta in Nginx config
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Kopiera PHP-kod från php-staget
COPY --from=php /var/www/html /var/www/html

# Exponera port 80
EXPOSE 80

# Starta nginx
CMD ["nginx", "-g", "daemon off;"]

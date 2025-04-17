FROM php:8.2-fpm-alpine AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

FROM nginx:stable-alpine

WORKDIR /var/www/html

COPY --from=composer /app .
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY .env.docker .env

# Certifique-se de que a extensão pdo_sqlite esteja instalada
RUN docker-php-ext-install pdo_sqlite

RUN php artisan optimize:clear

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
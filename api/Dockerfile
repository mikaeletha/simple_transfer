FROM php:8.2-fpm-alpine

WORKDIR /var/www

# Instala dependências e Composer
RUN apk add --no-cache git sqlite sqlite-dev curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala extensões necessárias
RUN docker-php-ext-install pdo pdo_sqlite

# Copia o projeto
COPY . .

# Instala dependências do Laravel
RUN composer install

# Gera chave e limpa cache
RUN php artisan key:generate \
    && php artisan optimize:clear

EXPOSE 9000

CMD ["php-fpm"]

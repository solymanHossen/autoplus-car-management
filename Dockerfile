FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    mysql-client \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN cp .env.example .env \
    && php artisan key:generate \
    && npm run build \
    && php artisan storage:link || true

EXPOSE 9000

CMD ["php-fpm"]

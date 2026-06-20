FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY docker/laravel/*.sh /usr/local/bin/

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY laravel-app /var/www/html

RUN chown -R www-data:www-data /var/www/html/bootstrap/cache \
 && chown -R www-data:www-data /var/www/html/storage \
 && chmod +x /usr/local/bin/*.sh

RUN composer install

EXPOSE 8000

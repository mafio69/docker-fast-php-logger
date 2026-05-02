FROM php:8.3-apache

# System deps + common PHP extensions
RUN apt-get update && apt-get install -y \
        git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: serve from /var/www/html/app
RUN sed -i 's|/var/www/html|/var/www/html/app|g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

# Install fast-php-logger
COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# App code mounted at runtime via volume
# Logs written to /var/www/html/logs (also mounted)

EXPOSE 80

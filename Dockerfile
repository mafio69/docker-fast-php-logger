FROM php:8.2-fpm-alpine

# Install dependencies + linux-headers dla sockets
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    git \
    unzip \
    sqlite \
    sqlite-dev \
    oniguruma-dev \
    libzip-dev \
    tzdata \
    linux-headers \
    && docker-php-ext-install pdo pdo_sqlite sockets pcntl

# Timezone
ENV TZ=Europe/Warsaw
RUN cp /usr/share/zoneinfo/Europe/Warsaw /etc/localtime

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /app

# Copy application
COPY . /app

# Install dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader 2>/dev/null || true; fi

# Create directories
RUN mkdir -p /app/logs /app/data /run/nginx && \
    chown -R www-data:www-data /app/logs /app/data

EXPOSE 80 8080

CMD ["./start-devbox.sh"]

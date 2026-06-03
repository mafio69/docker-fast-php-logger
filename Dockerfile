FROM php:8.4-fpm-alpine

# Install dependencies
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
    supervisor \
    linux-headers \
    openssh-client \
    sshpass \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql sockets pcntl

# Timezone
ENV TZ=Europe/Warsaw
RUN cp /usr/share/zoneinfo/Europe/Warsaw /etc/localtime

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Supervisor config (start nginx + php-fpm)
RUN mkdir -p /etc/supervisor/conf.d
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy application
COPY . /var/www/html

# Install dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Create directories
RUN mkdir -p /var/www/html/logs /var/www/html/data /run/nginx /var/log/supervisor && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/data

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

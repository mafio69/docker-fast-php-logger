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

# Copy application files
COPY . /var/www/html

# Configure git to trust the working directory to avoid "dubious ownership" errors during composer install
RUN git config --global --add safe.directory /var/www/html

# Install dependencies (ignoring corrupted lock file if present)
RUN if [ -f composer.json ]; then \
        rm -f composer.lock && \
        composer install --no-dev --optimize-autoloader; \
    fi

# Create directories
RUN mkdir -p /var/www/html/logs /var/www/html/data /run/nginx /var/log/supervisor && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/data

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

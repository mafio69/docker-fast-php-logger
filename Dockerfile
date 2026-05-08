FROM php:8.3-apache

# System deps + PHP extensions
RUN apt-get update && apt-get install -y \
        git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev wget \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && wget -q https://github.com/duckdb/duckdb/releases/latest/download/duckdb_cli-linux-amd64.zip \
    && unzip -q duckdb_cli-linux-amd64.zip -d /usr/local/bin \
    && chmod +x /usr/local/bin/duckdb \
    && rm duckdb_cli-linux-amd64.zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: app served from /var/www/html/app, viewer at /logs
RUN sed -i 's|/var/www/html|/var/www/html/app|g' /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite alias

# Add /logs alias pointing to the viewer entry point
RUN echo '\n\
Alias /logs /var/www/html/viewer\n\
<Directory /var/www/html/viewer>\n\
    Options -Indexes\n\
    AllowOverride None\n\
    Require all granted\n\
    DirectoryIndex index.php\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Trust the build directory for git
RUN git config --global --add safe.directory /var/www/html \
    && git config --global --add safe.directory /opt/project

# Install packages from GitHub — vendor stays inside the image
COPY composer.json ./
COPY packages/ ./packages/
ARG GITHUB_TOKEN
RUN composer config --global github-oauth.github.com "$GITHUB_TOKEN" && \
    COMPOSER_ALLOW_SUPERUSER=1 \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Copy viewer entry point
COPY viewer/ /var/www/html/viewer/

# Create directories
RUN mkdir -p /var/www/html/app /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]

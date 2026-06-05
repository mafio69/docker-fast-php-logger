ARG BASE_IMAGE=mafio69/php-env:8.4-fpm-alpine
FROM ${BASE_IMAGE}

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Supervisor config (start nginx + php-fpm)
RUN mkdir -p /etc/supervisor/conf.d
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Tell Git that this directory is safe to bypass the dubious ownership error
RUN git config --global --add safe.directory /var/www/html

# Copy application
COPY . /var/www/html

# Install dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader 2>/dev/null || true; fi

# Create directories
RUN mkdir -p /var/www/html/logs /var/www/html/data /var/www/html/viewer /var/www/.ssh /run/nginx /var/log/supervisor /run/sshd && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/data /var/www/html/viewer /var/www/.ssh && \
    chmod 700 /var/www/.ssh

# Configure SSH server for password authentication
RUN ssh-keygen -A && \
    sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config && \
    sed -i 's/#PubkeyAuthentication yes/PubkeyAuthentication yes/' /etc/ssh/sshd_config

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

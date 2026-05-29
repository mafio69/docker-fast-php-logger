FROM php:8.2-cli-alpine

# Install dependencies + GUI tools
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    bash \
    zenity \
    xdotool \
    tzdata \
    && docker-php-ext-install pcntl

# Set timezone
ENV TZ=Europe/Warsaw
RUN cp /usr/share/zoneinfo/Europe/Warsaw /etc/localtime && \
    echo "Europe/Warsaw" > /etc/timezone

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application
COPY . .

# Create logs directory
RUN mkdir -p logs var && chmod 777 logs var

CMD ["php", "bin/time-agent"]

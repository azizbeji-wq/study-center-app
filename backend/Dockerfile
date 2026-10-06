FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    sqlite-dev \
    oniguruma-dev \
    ca-certificates \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql bcmath opcache zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy backend files
COPY . .

# Install PHP dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for entrypoint script
RUN chmod +x /var/www/entrypoint.sh

EXPOSE 8000 10000

ENTRYPOINT ["/var/www/entrypoint.sh"]

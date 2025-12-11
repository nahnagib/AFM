# Use official PHP image with required extensions
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
       git unzip libzip-dev libonig-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set workdir
WORKDIR /var/www/html

# Copy project files
COPY . .

# Use production env template as base .env
RUN cp .env.example.production .env

# Install PHP dependencies and run Laravel post-install scripts
RUN composer install --no-dev --optimize-autoloader

# Laravel optimizations
RUN php artisan key:generate --force \
    && php artisan config:cache \
    && php artisan migrate --force \
    && php artisan db:seed --force

# Expose the port Render will map
EXPOSE 8080

# Start Laravel HTTP server, using Render's PORT env if present
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}

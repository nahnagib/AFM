# Use official PHP image with required extensions
FROM php:8.2-cli

# Install system dependencies + libs needed for gd
RUN apt-get update \
    && apt-get install -y \
       git unzip libzip-dev libonig-dev libpq-dev \
       libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*



# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set workdir
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose the port Render will map
EXPOSE 8080

# Start-up script handles env + migrations + server
CMD /bin/sh -c " \
    if [ ! -f .env ]; then \
        cp .env.example.production .env; \
    fi && \
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080} \
"

# Use official PHP image with required extensions
FROM php:8.2-cli

# Install system dependencies + libs needed for gd + mbstring
RUN apt-get update \
    && apt-get install -y \
       git unzip libzip-dev libonig-dev libpq-dev \
       libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        zip \
        gd \
        mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set workdir
WORKDIR /var/www/html

# Copy project files
COPY . .

# Create a minimal .env file INSIDE the image
# All sensitive values will be overridden by Render environment variables
RUN printf '%s\n' \
    'APP_NAME=AFM' \
    'APP_ENV=production' \
    'APP_KEY=' \
    'APP_DEBUG=false' \
    'APP_URL=' \
    '' \
    'LOG_CHANNEL=stack' \
    'LOG_LEVEL=debug' \
    '' \
    'DB_CONNECTION=mysql' \
    'DB_HOST=' \
    'DB_PORT=' \
    'DB_DATABASE=' \
    'DB_USERNAME=' \
    'DB_PASSWORD=' \
    '' \
    'SESSION_DRIVER=file' \
    'SESSION_LIFETIME=120' \
    'SESSION_DOMAIN=' \
    'SESSION_SECURE_COOKIE=false' \
    '' \
    'CACHE_DRIVER=file' \
    'QUEUE_CONNECTION=sync' \
    '' \
    'MAIL_MAILER=smtp' \
    'MAIL_HOST=mailpit' \
    'MAIL_PORT=1025' \
    'MAIL_USERNAME=null' \
    'MAIL_PASSWORD=null' \
    'MAIL_ENCRYPTION=null' \
    'MAIL_FROM_ADDRESS=noreply@afm.test' \
    'MAIL_FROM_NAME="AFM System"' \
    > .env

# Install PHP dependencies (NO artisan scripts during build)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Expose the port Render will map
EXPOSE 8080

# Start-up script: key, migrations, seed, server
CMD /bin/sh -c " \
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080} \
"

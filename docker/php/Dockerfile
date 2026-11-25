FROM php:8.3-fpm

# System dependencies + PHP extensions required by Laravel + our packages.
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    default-mysql-client \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        zip \
        intl \
        opcache \
    && docker-php-ext-enable opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app source (bind-mounted at runtime via docker-compose; this is for build-time
# composer install of the vendor tree inside the image).
COPY . /var/www/html

# Install PHP dependencies (production only; dev deps installed in CI container).
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && rm -rf /var/www/html/vendor \
    && composer clear-cache

# Laravel entrypoint: generate key, migrate, seed, cache config/routes if desired.
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
# Stage 1: Build dependencies
FROM composer:2 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Install dependencies ignoring platform requirements (since we might be on different arch) 
# and no-dev for production, though for this dev setup we might want dev deps? 
# The user wants "Production Grade" but is likely running in dev. 
# Let's stick to --no-dev for the "optimization" aspect, but maybe keep it simple.
# Actually, for the "test" phase we added dev deps. 
# Let's install everything for now to support running tests in container if needed, 
# or strictly follow "optimization" and do --no-dev. 
# Given the context of "System Modernization", let's do --no-dev --optimize-autoloader for the final image,
# but wait, if they want to run tests inside, they need dev deps.
# I'll stick to a standard optimized build.
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# Stage 2: Production Image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure PHP
COPY ./php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Copy application code
COPY public/ /var/www/html/
COPY src/ /var/www/src/
COPY config/ /var/www/config/
COPY docs/ /var/www/docs/

# Copy dependencies from vendor stage
COPY --from=vendor /app/vendor/ /var/www/vendor/

# Set permissions (optional but good practice)
# Create storage directory
RUN mkdir -p /var/www/storage/cache/ratelimit

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 80

CMD ["apache2-foreground"]

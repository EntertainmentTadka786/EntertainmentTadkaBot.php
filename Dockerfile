# Dockerfile - Fixed for Render.com
FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache modules enable karo
RUN a2enmod rewrite headers

# Composer install karo
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working directory set karo
WORKDIR /var/www/html

# Project files copy karo
COPY . .

# Apache configuration - FIXED: Removed /etc/hosts modification
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/cache \
    && touch /var/www/html/users.json \
    && touch /var/www/html/error.log \
    && touch /var/www/html/movies.csv \
    && touch /var/www/html/requests.json \
    && touch /var/www/html/reminders.json \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 666 /var/www/html/users.json \
    && chmod 666 /var/www/html/error.log \
    && chmod 666 /var/www/html/movies.csv \
    && chmod 666 /var/www/html/requests.json \
    && chmod 666 /var/www/html/reminders.json \
    && chmod 777 /var/www/html/cache

# PHP configuration
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Port expose karo
EXPOSE 80

# Health check for Render
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/?health || exit 1

# Start Apache
CMD ["apache2-foreground"]

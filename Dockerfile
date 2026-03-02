# Dockerfile
FROM php:8.2-apache

# System dependencies install karo
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions install karo
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Apache modules enable karo
RUN a2enmod rewrite headers

# Composer install karo
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working directory set karo
WORKDIR /var/www/html

# Project files copy karo
COPY . .

# Apache configuration
COPY .htaccess /var/www/html/.htaccess

# Permissions set karo
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 666 /var/www/html/users.json \
    && chmod 666 /var/www/html/error.log \
    && chmod 666 /var/www/html/movies.csv \
    && mkdir -p /var/www/html/cache \
    && chmod 777 /var/www/html/cache

# Apache config for Render
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Port expose karo
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
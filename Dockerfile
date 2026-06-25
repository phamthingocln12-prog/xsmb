FROM php:8.1-apache

# Cài đặt thư viện cần thiết
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql zip

# Cấu hình Apache
RUN a2enmod rewrite
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# BƯỚC QUAN TRỌNG: Cài đặt với flag ignore-platform-reqs để bỏ qua lỗi platform
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

EXPOSE 80
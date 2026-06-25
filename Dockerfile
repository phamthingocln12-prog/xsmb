# Sử dụng hình ảnh PHP có sẵn Apache
FROM php:8.1-apache

# Cài đặt các thư viện cần thiết cho Laravel
RUN apt-get update && apt-get install -y libzip-dev zip unzip git
RUN docker-php-ext-install zip pdo_mysql

# Cấu hình Apache để chạy Laravel
RUN a2enmod rewrite
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy toàn bộ code vào trong thùng Docker
COPY . /var/www/html

# Phân quyền
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Chạy composer install
RUN composer install --optimize-autoloader --no-dev
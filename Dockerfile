FROM php:8.1-apache

# Cài đặt các phần mở rộng PHP cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache

# Cấu hình Apache
RUN a2enmod rewrite
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Cài đặt Composer từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Chạy composer install với quyền bỏ qua check platform (để tránh lỗi phiên bản)
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# Expose cổng 80
EXPOSE 80
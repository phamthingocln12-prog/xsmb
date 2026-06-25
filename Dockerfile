FROM php:8.1-apache

# 1. Cài đặt các thư viện cần thiết
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql zip

# 2. Cấu hình Apache để nhận diện thư mục public của Laravel
# Mặc định Apache trỏ vào /var/www/html, ta cần nó trỏ vào /var/www/html/public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 3. Cấu hình Apache Rewrite (để chạy được URL thân thiện của Laravel)
RUN a2enmod rewrite

# 4. Copy mã nguồn vào container
COPY . /var/www/html

# 5. Phân quyền thư mục lưu trữ cho Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# 7. Cấp quyền thực thi cho file khởi động (ta sẽ tạo ở bước sau)
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

# 8. Chạy lệnh khởi động đa nhiệm
CMD ["/usr/local/bin/start.sh"]
# Stage 1: Build Vue
FROM node:18 AS frontend

WORKDIR /app

# Copy package + env
COPY package*.json ./
COPY .env.production .env

RUN npm install

# ✅ PHẢI copy toàn bộ source code vào trước khi build
COPY . .

RUN npm run build



# Stage 2: Laravel + Nginx + PHP-FPM
FROM richarvey/nginx-php-fpm:3.1.6

# Copy toàn bộ source code
COPY . /var/www/html
WORKDIR /var/www/html

# Copy file .env.production thành .env cho Laravel backend
COPY .env.production /var/www/html/.env

# Cài đặt PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction
# Xóa default nginx config và copy config mới
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-enabled/default.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-enabled/default

# Tạo các thư mục cần thiết cho Laravel
RUN mkdir -p /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Các biến môi trường
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY supervisord.conf /etc/supervisord.conf

# ĐẢM BẢO build mới luôn được copy vào cuối cùng
COPY --from=frontend /app/public/build /var/www/html/public/build

EXPOSE 80

COPY start-web.sh /start-web.sh
RUN chmod +x /start-web.sh

CMD ["/start-web.sh"]
# syntax=docker/dockerfile:1
# PartsDesk image for Render (or any Docker / OrbStack host): PHP 8.3 + Apache, web root = public/.

# ---- 1. PHP dependencies (no dev tools in the final image) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize

# ---- 2. Runtime ----
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/partsdesk.ini
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/partsdesk-entrypoint

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

RUN chmod +x /usr/local/bin/partsdesk-entrypoint \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername \
 && mkdir -p storage/logs public/uploads \
 && chown -R www-data:www-data storage public/uploads

# Render sets PORT; logs go to stderr so they show in the Render dashboard
ENV PORT=10000 LOG_CHANNEL=stderr APP_DEBUG=false
EXPOSE 10000

ENTRYPOINT ["partsdesk-entrypoint"]
CMD ["apache2-foreground"]

# لمعة — الـ Backend ولوحة الإدارة في حاوية واحدة (PHP 8.3 + Apache)
# يعمل على Render / Koyeb / أي خادم Docker.
FROM php:8.3-apache

# امتدادات PHP: MySQL و PostgreSQL (Neon) و intl (Filament) وغيرها
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_pgsql intl zip bcmath exif gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=12M\npost_max_size=40M\nmemory_limit=256M\nopcache.validate_timestamps=0\n" > "$PHP_INI_DIR/conf.d/lamaa.ini"

WORKDIR /var/www/html

# الاعتمادات أولاً (طبقة مخزنة)، ثم الكود
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/lamaa-start
RUN chmod +x /usr/local/bin/lamaa-start

EXPOSE 8080
CMD ["lamaa-start"]

FROM php:8.5.4-fpm AS dev

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./

RUN composer install --no-dev --optimize-autoloader
    
COPY . .

RUN mkdir -p storage logs var && \
    chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var

FROM php:8.5.4-fpm AS prod

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY --from=dev /var/www/html/vendor ./vendor
COPY --from=dev /var/www/html/server ./server
COPY --from=dev /var/www/html/index.php ./
COPY --from=dev /var/www/html/storage ./storage
COPY --from=dev /var/www/html/logs ./logs
COPY --from=dev /var/www/html/var ./var

RUN chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var

FROM ${ENVIRONMENT:-dev} AS final
FROM php:8.5.4-fpm AS dev

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --optimize-autoloader
    
COPY . .

RUN mkdir -p storage logs var && \
    chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var

FROM php:8.5.4-fpm AS prod

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader

COPY server ./server
COPY index.php ./
COPY . .

RUN mkdir -p storage logs var && \
    chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var 

FROM ${ENVIRONMENT:-dev} AS final

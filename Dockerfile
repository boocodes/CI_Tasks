ARG ENVIRONMENT=dev

FROM php:8.5-fpm AS dev

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --optimize-autoloader --prefer-dist

COPY . .

RUN mkdir -p storage logs var && \
    chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var

FROM php:8.5-fpm AS prod

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader --prefer-dist

COPY server ./server
COPY index.php ./
COPY . .

RUN mkdir -p storage logs var && \
    chown -R www-data:www-data storage logs var && \
    chmod -R 775 storage logs var

FROM ${ENVIRONMENT:-dev} AS final

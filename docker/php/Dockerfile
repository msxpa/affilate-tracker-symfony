FROM php:8.3-fpm-alpine

# Системные зависимости
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP расширения
RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    pgsql \
    zip \
    opcache

# Redis расширение (pecl)
RUN pecl install redis \
    && docker-php-ext-enable redis

# AMQP расширение (для RabbitMQ)
RUN apk add --no-cache rabbitmq-c-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI (опционально, но удобно)
RUN apk add --no-cache bash \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Рабочая директория
WORKDIR /var/www/html

# Права
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
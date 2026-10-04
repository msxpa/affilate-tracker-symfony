#affiliate-tracker-symfony/docker/php/Dockerfile
FROM php:8.4-fpm

# Системные зависимости для сборки расширений
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    librabbitmq-dev \
    libssl-dev \
    autoconf \
    g++ \
    make \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Встроенные расширения PHP (не требуют PECL)
RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    bcmath \
    sockets

# Redis — из исходников GitHub (PECL не работает)
ARG PHP_REDIS_VERSION=6.2.0
RUN curl -L -o /tmp/redis.tar.gz "https://github.com/phpredis/phpredis/archive/refs/tags/${PHP_REDIS_VERSION}.tar.gz" \
    && tar xfz /tmp/redis.tar.gz -C /tmp \
    && rm -f /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext/redis \
    && cp -a /tmp/phpredis-${PHP_REDIS_VERSION}/. /usr/src/php/ext/redis/ \
    && docker-php-ext-install redis \
    && rm -rf /tmp/phpredis-${PHP_REDIS_VERSION}

# AMQP — из исходников GitHub (v2.2.0)
ARG PHP_AMQP_VERSION=2.2.0
RUN curl -L -o /tmp/amqp.tar.gz "https://github.com/php-amqp/php-amqp/archive/refs/tags/v${PHP_AMQP_VERSION}.tar.gz" \
    && tar xfz /tmp/amqp.tar.gz -C /tmp \
    && rm -f /tmp/amqp.tar.gz \
    && mkdir -p /usr/src/php/ext/amqp \
    && cp -a /tmp/php-amqp-${PHP_AMQP_VERSION}/. /usr/src/php/ext/amqp/ \
    && cd /usr/src/php/ext/amqp \
    && phpize \
    && ./configure --with-librabbitmq \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable amqp \
    && rm -rf /tmp/php-amqp-${PHP_AMQP_VERSION}

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
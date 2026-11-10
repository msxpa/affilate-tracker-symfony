#docker/php/Dockerfile
# ==========================================
# Stage 1: RoadRunner binary
# ==========================================
FROM ghcr.io/roadrunner-server/roadrunner:3.0.0 AS roadrunner

# ==========================================
# Stage 2: PHP CLI
# ==========================================
FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    ca-certificates \
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

# PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    intl \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    bcmath \
    sockets

# ==========================================
# Redis extension
# ==========================================
ARG PHP_REDIS_VERSION=6.2.0

RUN curl -fsSL \
    -o /tmp/redis.tar.gz \
    "https://github.com/phpredis/phpredis/archive/refs/tags/${PHP_REDIS_VERSION}.tar.gz" \
    && mkdir -p /usr/src/php/ext/redis \
    && tar -xzf /tmp/redis.tar.gz \
        --strip-components=1 \
        -C /usr/src/php/ext/redis \
    && docker-php-ext-install redis \
    && rm -rf /tmp/redis.tar.gz

# ==========================================
# AMQP extension
# ==========================================
ARG PHP_AMQP_VERSION=2.2.0

RUN curl -fsSL \
    -o /tmp/amqp.tar.gz \
    "https://github.com/php-amqp/php-amqp/archive/refs/tags/v${PHP_AMQP_VERSION}.tar.gz" \
    && mkdir -p /usr/src/php/ext/amqp \
    && tar -xzf /tmp/amqp.tar.gz \
        --strip-components=1 \
        -C /usr/src/php/ext/amqp \
    && cd /usr/src/php/ext/amqp \
    && phpize \
    && ./configure --with-librabbitmq \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable amqp \
    && rm -rf /tmp/amqp.tar.gz /usr/src/php/ext/amqp

# ==========================================
# RoadRunner binary
# ==========================================
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

# ==========================================
# Composer
# ==========================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ==========================================
# Symfony CLI
# ==========================================
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

EXPOSE 8080

USER www-data

CMD ["rr", "serve", "-c", ".rr.yaml"]
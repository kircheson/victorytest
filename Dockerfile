FROM php:8.2-apache

# Устанавливаем необходимые зависимости для PostgreSQL и Redis
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    libzip-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# PECL устанавливаем расширение Redis и включаем
RUN pecl install redis \
    && docker-php-ext-enable redis

# Устанавливаем расширения PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer require predis/predis

# Копируем приложение
COPY . /var/www/html/
WORKDIR /var/www/html/

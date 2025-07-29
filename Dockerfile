FROM php:8.2-apache

# Устанавливаем необходимые зависимости для PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/* # Очищаем кэш apt для уменьшения размера образа

RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Устанавливаем predis для Redis (через Composer), если понадобится
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html/
WORKDIR /var/www/html/

# сертификат ca.pem
# (он уже в репозитории)

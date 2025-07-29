FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Устанавливаем predis для Redis (через Composer), если понадобится
RUN apt-get update && apt-get install -y unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . /var/www/html/
WORKDIR /var/www/html/

# сертификат ca.pem
# (он уже в репозитории)


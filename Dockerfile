FROM php:8.5-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY htdocs/ /var/www/html/
COPY docker/php/config.php /var/www/html/config/config.php
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version
WORKDIR /var/www/html/admin
RUN composer install --no-interaction
RUN chown -R www-data:www-data /var/www/html
RUN a2enmod rewrite
EXPOSE 80
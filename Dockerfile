FROM php:8.5-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY htdocs/ /var/www/html/
RUN mkdir /var/www/utils
COPY utils/ /var/www/utils/
COPY schema.sql /var/www/schema.sql
COPY docker/php/config.php /var/www/html/config/config.php
RUN rm -f /var/www/html/config/config.sample.php
RUN apt-get update && apt-get install -y curl git unzip && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/html
RUN composer install --no-interaction
RUN apt-get purge -y curl git unzip && apt-get autoremove -y
RUN chown -R www-data:www-data /var/www/html
RUN a2enmod rewrite
EXPOSE 80
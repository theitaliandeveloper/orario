# Orario Scuola, Copyright (C) 2025-2026 EmmeV.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see https://www.gnu.org/licenses/.

FROM php:8.5-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN pecl install redis && docker-php-ext-enable redis
RUN mkdir -p /var/lib/php/sessions \
	&& chown -R www-data:www-data /var/lib/php/sessions \
	&& chmod 700 /var/lib/php/sessions \
	&& printf "session.save_path = /var/lib/php/sessions\n" > /usr/local/etc/php/conf.d/zz-sessions.ini
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
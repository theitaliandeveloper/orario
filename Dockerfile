FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY htdocs/ /var/www/html/
COPY docker/php/db.php /var/www/html/db.php
RUN chown -R www-data:www-data /var/www/html
# For now remove OpenID files from container, OpenID integration will come soon.
RUN rm /var/www/html/admin/login.php.keycloak
RUN rm /var/www/html/admin/logout.php.keycloak
RUN a2enmod rewrite
EXPOSE 80
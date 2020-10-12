FROM php:apache
COPY docker/. /var/www/composer/
WORKDIR /var/www/composer
RUN ./install-composer.sh
COPY src/. /var/www/html/

TODO richtig machen

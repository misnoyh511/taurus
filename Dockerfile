FROM php:7.0-cli

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apt-get update && apt-get install -y unzip zip git openssl
RUN docker-php-ext-install pdo mbstring

WORKDIR /app

COPY composer.json .
COPY . .

RUN composer update

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=8080
FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update -y && apt-get install -y \
    git curl zip unzip libzip-dev gnupg \
 && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
 && apt-get update -y && apt-get install -y nodejs \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure zip \
 && docker-php-ext-install zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist --no-progress

RUN npm install \
 && npm run build

COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

RUN mkdir -p /var/www/html/public/build \
 && chown -R www-data:www-data /var/www

EXPOSE 80

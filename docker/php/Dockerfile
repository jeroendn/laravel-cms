FROM php:8.5-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    mc \
    telnet \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* # cleans up the cache

RUN docker-php-ext-install -j"$(nproc)" \
    mysqli \
    pdo \
    pdo_mysql

# Node + npm copied from the official image instead of Debian's nodejs/npm packages
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Enable apache
RUN a2enmod rewrite headers

# Point the docroot at Laravel's public/ and allow its .htaccess.
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf
RUN printf '<Directory /var/www/html/public>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

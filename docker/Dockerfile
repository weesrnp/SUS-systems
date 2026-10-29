# SUS-systems 3.x — PHP 8.3 + Apache (extensões ftp, zip, curl)
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev libcurl4-openssl-dev curl \
    && docker-php-ext-install ftp zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo "memory_limit=512M\nmax_execution_time=0\noutput_buffering=Off" > /usr/local/etc/php/conf.d/sus.ini

WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/cache && chown -R www-data:www-data storage

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -fs http://localhost/api/health || exit 1

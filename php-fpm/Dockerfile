FROM php:7.2-fpm

RUN apt-get update && \
  apt-get upgrade -y && \
  apt-get install -y --no-install-recommends \
    libpq-dev \
    libssl-dev \
    libmcrypt-dev \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-install pdo_pgsql

RUN usermod -u 1000 www-data

WORKDIR /var/www

CMD ["php-fpm"]

EXPOSE 9000
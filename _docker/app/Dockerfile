FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
      supervisor \
      curl \
      procps \
      nano \
      apt-utils \
      libpq-dev \
      libpng-dev \
      libzip-dev \
      zip unzip \
      git  \
      build-essential \
      && \
      curl -fsSL https://deb.nodesource.com/setup_24.x | bash - && \
      apt-get install -y nodejs && \
      npm install -g npm@latest && \
      pecl install redis && \
      docker-php-ext-enable redis && \
      docker-php-ext-install pdo_pgsql && \
      docker-php-ext-install bcmath && \
      docker-php-ext-install gd && \
      docker-php-ext-install zip && \
      apt-get remove -y build-essential && \
      apt-get autoremove -y && \
      apt-get clean && \
      rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./_docker/app/php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www


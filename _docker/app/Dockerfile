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
COPY ./_docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /home/www-data/.npm /home/www-data/.composer && \
    chown -R www-data:www-data /home/www-data /etc/supervisor /var/www

USER www-data

WORKDIR /var/www


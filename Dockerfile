FROM eduzz/php:7.3-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /usr/share/nginx/html

RUN set -x \
    && apt-get update \
    && apt-get install -y libcurl4-openssl-dev curl libzip-dev zlib1g-dev zip unzip openssh-client git \
    && docker-php-ext-install -j$(nproc) curl zip \
    && pecl install xdebug-2.8.0 \
    && docker-php-ext-enable xdebug 


RUN pecl install zip \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /usr/share/nginx/html
COPY . /usr/share/nginx/html

RUN ln -sf /proc/1/fd/2 /var/log/slow.log && \ 
    ls -lah

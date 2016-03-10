FROM php:7.0.3-cli

RUN apt-get update \
    && apt-get install -y git curl bzip2 vim libssl-dev zlib1g-dev libxrender1 libicu-dev g++ libjpeg-dev libjpeg62 libfontconfig-dev vim libpq-dev  \
    && docker-php-ext-install zip mbstring intl opcache pdo_mysql pdo_pgsql \
    && apt-get install -y mysql-client postgresql-client

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/bin/composer

WORKDIR /home/cocoders/event-store

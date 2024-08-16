FROM php:7.4.1-fpm
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apt-get update \
  && apt-get install -y git \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
COPY . /app/

WORKDIR /app
RUN composer install

ENTRYPOINT [ "php", "./index.php" ]

FROM php:7.4.1-fpm
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apt update && apt install -y git cron nano
COPY . /app/

WORKDIR /app
RUN composer install
RUN php ./index.php

# Optional: run the script in cron 
# RUN (crontab -l ; echo "42 * * * * cd /app && php ./index.php > /tmp/oci.log 2>&1") | crontab

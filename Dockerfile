FROM ubuntu:20.04

ARG DEBIAN_FRONTEND=noninteractive 

ENV RETRY_DELAY_TIME 10

COPY . /app/oci-arm-host-capacity

RUN apt update && apt install -y git php-cli php-curl php-xml jq nano unzip curl && \
    mkdir /compose && \
    useradd -r -s /bin/false ubuntu; \
    groupadd docker; \
    usermod -aG docker ubuntu; \
    cd /compose && \
    curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    HASH=`curl -sS https://composer.github.io/installer.sig` && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    cd /app/oci-arm-host-capacity && \
    composer update && \
    composer install && \
    chown ubuntu:ubuntu /app/oci-arm-host-capacity

WORKDIR /app/oci-arm-host-capacity   


COPY run.sh run.sh
RUN chmod +x run.sh
RUN chown ubuntu:ubuntu run.sh

USER ubuntu    

CMD ./run.sh
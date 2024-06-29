#
# Build image: install dependencies with Composer
# 
FROM composer:2.7 AS build

WORKDIR /usr/src/app/

COPY composer.* ./

RUN composer install

#
# Build the runtime image
#
FROM php:8.2-cli AS runtime

# Set working directory
WORKDIR /usr/src/app

# Install necessary PHP extensions and other dependencies
RUN apt update && \
    apt install -y cron && \
    apt clean

# Copy project files to the working directory
COPY . /usr/src/app

# Get dependencys from Composer
COPY --from=build /usr/src/app/vendor /usr/src/app/vendor

# Copy the entrypoint script
RUN mv /usr/src/app/scripts/entrypoint.sh /entrypoint.sh && \
    chmod +x /entrypoint.sh && \
    chmod +x /usr/src/app/scripts/run.sh

# Create the log file to be able to run tail
RUN touch /var/log/cron.log && \
    chmod 777 /var/log/cron.log

# Add the cron job to the crontab file (every 5 minutes)
RUN echo '*/5 * * * * /usr/src/app/scripts/run.sh >> /var/log/cron.log' > /etc/cron.d/oci && \
    chmod 0644 /etc/cron.d/oci && \
    crontab /etc/cron.d/oci

# Create a volume for certificates
VOLUME /etc/ssl/certs

#
# Set environment variables
#
ENV OCI_PRIVATE_KEY_FILENAME="/private_key.pem"

ENV OCI_REGION=eu-frankfurt-1
ENV OCI_OCPUS=4
ENV OCI_MEMORY_IN_GBS=24
ENV OCI_SHAPE=VM.Standard.A1.Flex

ENV OCI_MAX_INSTANCES=1
ENV CACHE_AVAILABILITY_DOMAINS=1
ENV TOO_MANY_REQUESTS_TIME_WAIT=600

ENTRYPOINT [ "/entrypoint.sh" ]

# Run the command on container startup
CMD /usr/local/bin/php /usr/src/app/index.php && cron && tail -f /var/log/cron.log

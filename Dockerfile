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

# Add the cron job to the crontab file
RUN echo '* * * * * /usr/src/app/scripts/run.sh >> /var/log/cron.log' > /etc/cron.d/oci && \
    chmod 0644 /etc/cron.d/oci && \
    crontab /etc/cron.d/oci

# Create a volume for certificates
VOLUME /etc/ssl/certs

#
# Set environment variables
#
ENV OCI_REGION=eu-frankfurt-1
ENV OCI_USER_ID=ocid1.user.oc1..aaaaaaaax72***kd3q
ENV OCI_TENANCY_ID=ocid1.tenancy.oc1..aaaaaaaakpx***qmpa
ENV OCI_KEY_FINGERPRINT=b3:a5:90:***:b0:8d:1c
# absolute path (including directories) or direct public accessible URL
ENV OCI_PRIVATE_KEY_FILENAME="/path/to/oracleidentitycloudservice_***-07-14-10-35.pem"
ENV OCI_SUBNET_ID=ocid1.subnet.oc1.eu-frankfurt-1.aaaaaaaaahbb***faq
ENV OCI_IMAGE_ID=ocid1.image.oc1.eu-frankfurt-1.aaaaaaaa23z***v6wa
# Always free ARM: 1,2,3,4. Always free AMD x64: 1
ENV OCI_OCPUS=4
# Always free ARM: 6,12,18,24. NB! Oracle Linux Cloud Developer Image requires minimum 8. Always free AMD x64: 1
ENV OCI_MEMORY_IN_GBS=24
# Or "VM.Standard.E2.1.Micro" for Always free AMD x64
ENV OCI_SHAPE=VM.Standard.A1.Flex
ENV OCI_MAX_INSTANCES=1
# Your public key ~/.ssh/id_rsa.pub contents
# NB! No new lines / line endings allowed! Put inside double quotes
ENV OCI_SSH_PUBLIC_KEY="ssh-ed25519 AAAAC3N***o2U user@example.com"

ENV CACHE_AVAILABILITY_DOMAINS=1
ENV TOO_MANY_REQUESTS_TIME_WAIT=600

ENTRYPOINT [ "/entrypoint.sh" ]

# Run the command on container startup
CMD /usr/src/app/scripts/run.sh && cron && tail -f /var/log/cron.log

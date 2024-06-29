#!/bin/sh

# Check if necessary environment variables are set
REQUIRED_VARS="OCI_USER_ID OCI_TENANCY_ID OCI_KEY_FINGERPRINT OCI_SUBNET_ID OCI_IMAGE_ID OCI_SSH_PUBLIC_KEY OCI_PRIVATE_KEY_FILENAME"

for VAR in $REQUIRED_VARS; do
    if [ -z "$(eval echo \$$VAR)" ]; then
        echo "[ENTRYPOINT] Error: $VAR is not set."
        exit 1
    fi
done
echo "[ENTRYPOINT] All required environment variables are set."

# Check if the private key file exists
if [ ! -f "$OCI_PRIVATE_KEY_FILENAME" ]; then
    echo "[ENTRYPOINT] Error: Private key file $OCI_PRIVATE_KEY_FILENAME (ENV: OCI_PRIVATE_KEY_FILENAME) does not exist."
    exit 1
else
    echo "[ENTRYPOINT] Private key file $OCI_PRIVATE_KEY_FILENAME exists."
fi


# Export the environment variables to the environment file
printenv | grep OCI_ > /etc/environment
echo "[ENTRYPOINT] Environment variables written to /etc/environment"

echo "[ENTRYPOINT] Container erfolgreich gestartet..."
echo "[ENTRYPOINT] Cron Job wird alle 5 Minuten ausgeführt..."

# Execute the passed command
exec "$@"

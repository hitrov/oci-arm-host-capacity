#!/bin/sh

echo "[RUN] Starting run job..."

# Source the environment variables
. /etc/environment

# Execute the PHP script
/usr/local/bin/php /usr/src/app/index.php

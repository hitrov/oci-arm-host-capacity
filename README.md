# OCI Host Capacity bypassing

## This script should be run as cron job (e.g. every minute).

It will call Oracle Cloud Infrastructure API trying to create an instance as it responds very often with 'Out of host capacity'. When additional OCI capacity will appear in your Home Region / Availability domain - instance will be created during the next execution.

Please review index.php for additional information.

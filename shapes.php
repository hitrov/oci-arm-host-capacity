<?php

// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";

use Dotenv\Dotenv;
use Hitrov\Interfaces\NotifierInterface;
use Hitrov\Notification\Telegram;
use Hitrov\OciApi;
use Hitrov\OciConfig;

$envFilename = empty($argv[1]) ? '.env' : $argv[1];
$dotenv = Dotenv::createUnsafeImmutable(__DIR__, $envFilename);
$dotenv->safeLoad();

$config = new OciConfig(
    getenv('OCI_REGION'),
    getenv('OCI_USER_ID'),
    getenv('OCI_TENANCY_ID'),
    getenv('OCI_KEY_FINGERPRINT'),
    getenv('OCI_PRIVATE_KEY_FILENAME'),
    '', // OCI_AVAILABILITY_DOMAIN not needed
    '', // OCI_SUBNET_ID not needed,
    '', // OCI_IMAGE_ID not needed,
);

$notifier = (function (): NotifierInterface {
/*
 if you have own https://core.telegram.org/bots
 and set TELEGRAM_BOT_API_KEY and your TELEGRAM_USER_ID in .env,
 or implemented NotifierInterface to e.g. send SMS or email -
 you can be notified whenever shapes will become available.
 */
return new Telegram();
})();

$api = new OciApi();
$shapes = $api->getShapes($config);

if ($shapes) {
    $notifier->notify(json_encode($shapes, JSON_PRETTY_PRINT));
}

var_dump($shapes);

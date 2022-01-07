<?php
declare(strict_types=1);


// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";

use Hitrov\Exception\ApiCallException;
use Hitrov\OciApi;
use Hitrov\OciConfig;

/*
 * availabilityDomain(s) now optional for ARM,
 * but you can provide either string or array.
 * if null or '' is provided, ListAvailabilityDomains API call be used for retrieval.
 * @see https://docs.oracle.com/en-us/iaas/api/#/en/identity/20160918/AvailabilityDomain/ListAvailabilityDomains
 *
 * NB! AMD (x86) Always Free instances should be created only in "main" availability domain.
 * Specify manually in this case.
 */
$config1 = new OciConfig(
    getenv('OCI_REGION') ?: 'us-phoenix-1', // region
    getenv('OCI_USER_ID') ?: 'ocid1.user.oc1..aaaaaaaa***', // user
    getenv('OCI_TENANCY_ID') ?: 'ocid1.tenancy.oc1..aaaaaaaaa***', // tenancy
    getenv('OCI_KEY_FINGERPRINT') ?: '42:b1:***:5b:2c', // fingerprint
    getenv('OCI_PRIVATE_KEY_FILENAME') ?: "oracleidentitycloudservice_oracle-***.pem", // key_file
    'FeVO:EU-FRANKFURT-1-AD-2',
    getenv('OCI_SUBNET_ID') ?: 'ocid1.subnet.oc1.phx.aaaaaaaa***', // subnetId
    getenv('OCI_IMAGE_ID') ?: 'ocid1.image.oc1.phx.aaaaaaaay***', // imageId
    (int) getenv('OCI_OCPUS') ?: 4,
    (int) getenv('OCI_MEMORY_IN_GBS') ?: 24
);

$configs = [
    $config1,
    // array of configs is used for the case when you have multiple accounts in different home regions
];

$api = new OciApi();
$notifier = (function (): \Hitrov\Interfaces\NotifierInterface {
    return new \Hitrov\Notification\Telegram();
})();

foreach ($configs as $config) {
    $shape = getenv('OCI_SHAPE') ?: 'VM.Standard.A1.Flex'; // or VM.Standard.E2.1.Micro

    $maxRunningInstancesOfThatShape = 1;
    if (getenv('OCI_MAX_INSTANCES') !== false) {
        $maxRunningInstancesOfThatShape = (int) getenv('OCI_MAX_INSTANCES');
    }

    $instances = $api->getInstances($config);

    $existingInstances = $api->checkExistingInstances($config, $instances, $shape, $maxRunningInstancesOfThatShape);
    if ($existingInstances) {
        echo "$existingInstances\n";
        continue;
    }

    $sshKey = getenv('OCI_SSH_PUBLIC_KEY') ?: 'ssh-rsa AAAAB3NzaC1...p5m8= ubuntu@localhost'; // ~/.ssh/id_rsa.pub contents

    if (!empty($config->availabilityDomains)) {
        if (is_array($config->availabilityDomains)) {
            $availabilityDomains = $config->availabilityDomains;
        } else {
            $availabilityDomains = [ $config->availabilityDomains ];
        }
    } else {
        $availabilityDomains = $api->getAvailabilityDomains($config);
    }

    foreach ($availabilityDomains as $availabilityDomainEntity) {
        $availabilityDomain = is_array($availabilityDomainEntity) ? $availabilityDomainEntity['name'] : $availabilityDomainEntity;
        try {
            $instanceDetails = $api->createInstance($config, $shape, $sshKey, $availabilityDomain);
        } catch(ApiCallException $e) {
            $message = $e->getMessage();
            echo "$message\n";
//            if ($notifier->isSupported()) {
//                $notifier->notify($message);
//            }

            if (
                $e->getCode() === 500 &&
                strpos($message, 'InternalError') !== false &&
                strpos($message, 'Out of host capacity') !== false
            ) {
                // trying next availability domain
                continue;
            }

            // current config is broken
            break;
        }

        // success
        $message = json_encode($instanceDetails, JSON_PRETTY_PRINT);
        echo "$message\n";
        if ($notifier->isSupported()) {
            $notifier->notify($message);
        }

        break;
    }
}

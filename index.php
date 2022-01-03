<?php
declare(strict_types=1);


// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";

use Hitrov\Exception\ApiCallException;
use Hitrov\OciApi;
use Hitrov\OciConfig;

/*
 * availabilityDomain(s) now optional,
 * but you can provide either string or array
 */
$config1 = new OciConfig(
    getenv('OCI_REGION') ?: 'us-phoenix-1', // region
    getenv('OCI_USER_ID') ?: 'ocid1.user.oc1..aaaaaaaa***', // user
    getenv('OCI_TENANCY_ID') ?: 'ocid1.tenancy.oc1..aaaaaaaaa***', // tenancy
    getenv('OCI_KEY_FINGERPRINT') ?: '42:b1:***:5b:2c', // fingerprint
    getenv('OCI_PRIVATE_KEY_FILENAME') ?: "oracleidentitycloudservice_oracle-***.pem", // key_file
    getenv('OCI_AVAILABILITY_DOMAIN'), // availabilityDomain(s): 'jYtI:PHX-AD-1' or ['jYtI:PHX-AD-1','jYtI:PHX-AD-2'] or null
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
        try {
            $instance = $api->createInstance($config, $shape, $sshKey, $availabilityDomainEntity['name']);
        } catch(ApiCallException $e) {
            echo "{$e->getMessage()}\n";

            // try another availability domain
            continue;
        }

        // success
        echo json_encode($instance, JSON_PRETTY_PRINT) . "\n";

        break;
    }
}

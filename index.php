<?php

// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";
use Hitrov\OciApi;
use Hitrov\OciConfig;

$config1 = new OciConfig(
    getenv('OCI_REGION') ?? 'us-phoenix-1', // region
    getenv('OCI_USER_ID') ?? 'ocid1.user.oc1..aaaaaaaa***', // user
    getenv('OCI_TENANCY_ID') ?? 'ocid1.tenancy.oc1..aaaaaaaaa***', // tenancy
    getenv('OCI_KEY_FINGERPRINT') ?? '42:b1:***:5b:2c', // fingerprint
    getenv('OCI_PRIVATE_KEY_FILENAME') ?? "oracleidentitycloudservice_oracle-***.pem", // key_file
    getenv('OCI_AVAILABILITY_DOMAIN') ?? 'jYtI:PHX-AD-1', // availabilityDomain
    getenv('OCI_SUBNET_ID') ?? 'ocid1.subnet.oc1.phx.aaaaaaaa***', // subnetId
    getenv('OCI_IMAGE_ID') ?? 'ocid1.image.oc1.phx.aaaaaaaay***', // imageId
    getenv('OCI_OCPUS') ?? 4,
    getenv('OCI_MEMORY_IN_GBS') ?? 24
);

$configs = [
    $config1,
    // array of configs is used for the case when you have multiple accounts in different home regions
];

$api = new OciApi();

foreach ($configs as $config) {
    $shape = getenv('OCI_SHAPE') ?? 'VM.Standard.A1.Flex'; // or VM.Standard.E2.1.Micro
    $maxRunningInstancesOfThatShape = getenv('OCI_MAX_INSTANCES') ?? 1;

    [ $listResponse, $listError, $listInfo ] = $api->getInstances($config);

    if ($listError || (!empty($listInfo) && $listInfo['http_code'] !== 200)) {
        echo "$listError: $listResponse\n";
        continue;
    }

    $listR = json_decode($listResponse, true);
    if (json_last_error() || !is_array($listR)) {
        echo "Got JSON error while getting instances or non-array. Response: $listResponse. User: $config->ociUserId\n";
        continue;
    }

    $existingInstances = $api->checkExistingInstances($config, $listR, $shape, $maxRunningInstancesOfThatShape);
    if ($existingInstances) {
        echo "$existingInstances\n";
        continue;
    }

    $sshKey = getenv('OCI_SSH_PUBLIC_KEY') ?? 'ssh-rsa AAAAB3NzaC1...p5m8= ubuntu@localhost'; // ~/.ssh/id_rsa.pub contents
    [ $response, $error, $info ] = $api->createInstance($config, $shape, $sshKey);

    $r = json_decode($response, true);
    $prettifiedResponse = json_encode($r, JSON_PRETTY_PRINT);
    echo $prettifiedResponse;
}

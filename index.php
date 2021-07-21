<?php

// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";
use Hitrov\OCI\Signer;
use Hitrov\OciConfig;

$config1 = new OciConfig(
    'us-phoenix-1', // region
    'ocid1.user.oc1..aaaaaaaa***', // user
    'ocid1.tenancy.oc1..aaaaaaaaa***', // tenancy
    '42:b1:***:5b:2c', // fingerprint
    "oracleidentitycloudservice_oracle-***.pem", // key_file
    'jYtI:PHX-AD-1', // availabilityDomain
    'ocid1.subnet.oc1.phx.aaaaaaaa***', // subnetId
    'ocid1.image.oc1.phx.aaaaaaaay***', // imageId
    4,
    24,
);

$configs = [
    $config1,
    // array of configs is used for the case when you have multiple accounts in different home regions
];

foreach ($configs as $config) {
    $shape = 'VM.Standard.A1.Flex';
    $maxRunningInstancesOfThatShape = 1;

    [ $listResponse, $listError, $listInfo ] = getInstances($config);

    if ($listError || (!empty($listInfo) && $listInfo['http_code'] !== 200)) {
        echo "$listError: $listResponse\n";
        continue;
    }

    $listR = json_decode($listResponse, true);
    if (json_last_error() || !is_array($listR)) {
        echo "Got JSON error while getting instances or non-array. Response: $listResponse. User: $config->ociUserId\n";
        continue;
    }

    $existingInstances = checkExistingInstances($config, $listR, $shape, $maxRunningInstancesOfThatShape);
    if ($existingInstances) {
        echo "$existingInstances\n";
        continue;
    }

    [ $response, $error, $info ] = createInstance($config, $shape);

    $r = json_decode($response, true);
    $prettifiedResponse = json_encode($r, JSON_PRETTY_PRINT);
    echo $prettifiedResponse;
}

function getApiUrl(OciConfig $config): string
{
    return "https://iaas.{$config->region}.oraclecloud.com/20160918/instances/";
}


function createInstance(OciConfig $config, string $shape): array
{
    $curl = curl_init();

    $method = 'POST';
    $url = getApiUrl($config);

    $sshKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDEo4YnJaaaE6HGTUheQ6LTgH08sHPKqbuPlv+JxLHRwzCUKXF6471RYWtdukn4zD69QUxL8DXsX73x20F7qqGGt0VthcLZzImC8iGFyB8tJIzTn/yLU6iWHPwngMcox/WzfXHjmpRoucXbi/V4VUeQrooOi1gESmM4CrWB5ZsEM2jUdLBmbW/zcrVdZeuZG+EUCJoupyHRLIhywqkLQukmkYPvbMeR+4PVImFZh8V15d5lOfTAQsw/j8I/b9ehvzVLqk7Weo9BpAwn6Ac2Kcjw6jO0K13tfGW75tTMy3AgHBpjZ20qPe9daaUU9F+KOI6STYKwDjw9tmbe+G6hce0BuMLPSCex9r/QWJd41tcqRY3S1mV8dM/ccI2lCL7PrQuKTjhE9MnSHDzGb1OVjtwC+bqIQ7/45JzJrlZ3KzUlcCSRir69G0pfwKUKvYgQQNfvR48ED9gW9MCQmU1OIbQjkPTtP73I/lDZSyze1PmXCuLOTpG5echr3xDJRq6p5m8= xitroff@MacBook-Air-Alexander.local'; // ~/.ssh/id_rsa.pub contents
    $displayName = 'instance-20210714-1042';

    $body = <<<EOD
{
    "metadata": {
        "ssh_authorized_keys": "$sshKey"
    },
    "shape": "$shape",
    "compartmentId": "{$config->tenancyId}",
    "displayName": "$displayName",
    "availabilityDomain": "{$config->availabilityDomain}",
    "sourceDetails": {
        "sourceType": "image",
        "imageId": "{$config->imageId}"
    },
    "createVnicDetails": {
        "assignPublicIp": false,
        "subnetId": "{$config->subnetId}",
        "assignPrivateDnsRecord": true
    },
    "agentConfig": {
        "pluginsConfig": [
            {
                "name": "Compute Instance Monitoring",
                "desiredState": "ENABLED"
            }
        ],
        "isMonitoringDisabled": false,
        "isManagementDisabled": false
    },
    "definedTags": {},
    "freeformTags": {},
    "instanceOptions": {
        "areLegacyImdsEndpointsDisabled": false
    },
    "availabilityConfig": {
        "recoveryAction": "RESTORE_INSTANCE"
    },
    "shapeConfig": {
        "ocpus": {$config->ocpus},
        "memoryInGBs": {$config->memoryInGBs}
    }
}
EOD;

    $signer = new Signer(
        $config->tenancyId,
        $config->ociUserId,
        $config->keyFingerPrint,
        $config->privateKeyFilename
    );

    $headers = $signer->getHeaders($url, $method, $body, 'application/json');

    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ];

    $curlOptions[CURLOPT_POSTFIELDS] = $body;

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    return [ $response, $error, $info ];
}

function getInstances(OciConfig $config): array
{
    $curl = curl_init();

    $baseUrl = getApiUrl($config);
    $url = "$baseUrl?availabilityDomain={$config->availabilityDomain}&compartmentId={$config->tenancyId}";

    $signer = new Signer(
        $config->tenancyId,
        $config->ociUserId,
        $config->keyFingerPrint,
        $config->privateKeyFilename
    );

    $headers = $signer->getHeaders($url);

    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => $headers,
    ];

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    return [ $response, $error, $info ];
}

function checkExistingInstances(OciConfig $config, array $listResponse, string $shape, int $maxRunningInstancesOfThatShape): string
{
    $existingInstances = array_filter($listResponse, function ($instance) use ($shape) {
//        $unacceptableStates = ['RUNNING', 'PROVISIONING', 'STARTING', 'STOPPED', 'STOPPING', 'TERMINATING'];
        $acceptableStates = ['TERMINATED'];
        return !in_array($instance['lifecycleState'], $acceptableStates) && $instance['shape'] === $shape;
    });

    if (count($existingInstances) < $maxRunningInstancesOfThatShape) {
        return '';
    }

    $displayNames = array_map(function ($instance) {
        return $instance['displayName'];
    }, $existingInstances);
    $displayNamesString = implode(', ', $displayNames);

    $lifecycleStates = array_map(function ($instance) {
        return $instance['lifecycleState'];
    }, $existingInstances);
    $lifecycleStatesString = implode(', ', $lifecycleStates);

    return "Already have an instance(s) [$displayNamesString] in state(s) (respectively) [$lifecycleStatesString]. User: $config->ociUserId\n";
}

<?php

// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";
use Hitrov\OCI\Signer;
use Hitrov\OciConfig;

// when you create API key in OCI Console,
// save the Configuration File Preview output, e.g.
//[DEFAULT]
//user=ocid1.user.oc1..aaaaaaaa***
//fingerprint=42:b1:***:5b:2c
//tenancy=ocid1.tenancy.oc1..aaaaaaaaa***
//region=us-phoenix-1
//key_file=<path to your private keyfile>

$config1 = new OciConfig(
    'us-phoenix-1', // region above
    'ocid1.user.oc1..aaaaaaaa***', // user above
    'ocid1.tenancy.oc1..aaaaaaaaa***', // tenancy above
    '42:b1:***:5b:2c', // fingerprint above
    "{$pathPrefix}oracleidentitycloudservice_oracle-***.pem", // key_file above
    'jYtI:PHX-AD-1', // availabilityDomain below
    'ocid1.subnet.oc1.phx.aaaaaaaa***', // subnetId below
    'ocid1.image.oc1.phx.aaaaaaaay***', // imageId below
);

// 1. begin your attempt to create ARM instance in the browser
// 2. make sure "Always Free Eligible" Availability Domain is chosen
// 3. in "Add SSH keys" section select "Paste public keys" and set the value of your e.g. ~/.ssh/id_rsa.pub
// 4. expand "Networking" section and select "Do not assign a public IPv4 address" (you can assign it later as only two are available for free)
// 5. before clicking "create" open dev tools
// 6. click "create", wait and "copy as curl" request to the endpoint https://iaas.{region}.oraclecloud.com/20160918/instances/
// 7. collect availabilityDomain, imageId, subnetId

$configs = [
    $config1,
    // array of configs is used for the case when you have multiple accounts in different home regions
];

foreach ($configs as $config) {
    // see below: in case of success the file with JSON response will be created,
    // and the operation won't execute again for this oci user
    $filename = "{$pathPrefix}$config->ociUserId";
    if (file_exists($filename)) {
        continue;
    }

    $shape = 'VM.Standard.A1.Flex';
    $sshKey = 'ssh-rsa AAAA ...= user@localhost'; // ~/.ssh/id_rsa.pub contents
    $displayName = 'instance-20210714-1042';

    $body = <<<EOD
{
    "metadata": {
        "ssh_authorized_keys": "$sshKey"
    },
    "shape": "$shape",
    "compartmentId": "{$config->compartmentId}",
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

    $url = "https://iaas.{$config->region}.oraclecloud.com/20160918/instances/";

    $signer = new Signer(
        $config->compartmentId,
        $config->ociUserId,
        $config->keyFingerPrint,
        $config->privateKeyFilename
    );

    $curl = curl_init();

    $method = 'POST';

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
    $r = json_decode($response, true);
    $prettifiedResponse = json_encode($r, JSON_PRETTY_PRINT);
    echo $prettifiedResponse;

    curl_close($curl);

    if (
        ($r['code'] === 'InternalError' && $r['message'] === 'Out of host capacity.')
        ||
        ($r['code'] === 'LimitExceeded' && strpos($r['message'], 'The following service limits were exceeded:') !== false)
    ) {
        continue;
    }

    // make sure directory is writeable by PHP script,
    // or file won't be created and script will never skip the same instance creation the for current user.
    // though, in case if instance will be created finally,
    // API will send back LimitExceeded, so you can stop your crob job later
    file_put_contents($filename, $prettifiedResponse);
}

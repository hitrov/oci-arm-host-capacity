<?php

namespace Hitrov\Test\Traits;

use Hitrov\OciApi;
use Hitrov\OciConfig;

trait DefaultConfig
{
    protected static OciApi $api;
    protected static OciConfig $config;

    public function getDefaultConfig(): OciConfig
    {
        return new OciConfig(
            getenv('OCI_REGION'),
            getenv('OCI_USER_ID'),
            getenv('OCI_TENANCY_ID'),
            getenv('OCI_KEY_FINGERPRINT'),
            getenv('OCI_PRIVATE_KEY_FILENAME'),
            getenv('OCI_AVAILABILITY_DOMAIN'),
            getenv('OCI_SUBNET_ID'),
            getenv('OCI_IMAGE_ID'),
            (int) getenv('OCI_OCPUS'),
            (int) getenv('OCI_MEMORY_IN_GBS')
        );
    }

    public function getDefaultApi(): OciApi
    {
        return new OciApi();
    }
}

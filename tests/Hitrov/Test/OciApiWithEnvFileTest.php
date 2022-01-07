<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Dotenv\Dotenv;

class OciApiWithEnvFileTest extends OciApiTest
{
    protected function setEnv(): void
    {
        putenv('OCI_SHAPE');
        putenv('OCI_OCPUS');
        putenv('OCI_MEMORY_IN_GBS');
        putenv('OCI_AVAILABILITY_DOMAIN');
        putenv('OCI_IMAGE_ID');
        putenv('OCI_SUBNET_ID');

        $envFileDir = getcwd() . '/tests/resources';
        $dotenv = Dotenv::createUnsafeMutable($envFileDir, '.env.test');
        $dotenv->safeLoad();
    }
}

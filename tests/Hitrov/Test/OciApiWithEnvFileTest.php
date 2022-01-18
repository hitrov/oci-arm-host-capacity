<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Test\Traits\LoadEnv;

class OciApiWithEnvFileTest extends OciApiTest
{
    use LoadEnv;

    const ENV_FILENAME = '.env.test';

    public function testGetAvailabilityDomains(): void
    {
        $this->markTestSkipped('Not relevant.');
    }

    protected function setEnv(): void
    {
        putenv('OCI_SHAPE');
        putenv('OCI_OCPUS');
        putenv('OCI_MEMORY_IN_GBS');
        putenv('OCI_AVAILABILITY_DOMAIN');
        putenv('OCI_IMAGE_ID');
        putenv('OCI_SUBNET_ID');

        $this->loadEnv();
    }
}

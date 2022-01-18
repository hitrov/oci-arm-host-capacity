<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Exception\ApiCallException;

class BootVolumeSizeTest extends BootVolumeIdTest
{
    const ENV_FILENAME = '.env.boot_volume_size.test';

    /**
     * @covers OciApi::createInstance
     * @covers \Hitrov\OciConfig::setBootVolumeSizeInGBs
     */
    public function testCreateInstance(): void
    {
        $exceptionThrown = false;
        try {
            self::$config->setBootVolumeSizeInGBs(getenv('OCI_BOOT_VOLUME_SIZE_IN_GBS'));
            $instance = self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
        } catch(ApiCallException $e) {
            $response = $e->getMessage();
            $this->assertEquals(400, $e->getCode());
            $this->assertTrue(strpos($response, 'QuotaExceeded') !== false);
            $this->assertTrue(strpos($response, 'Service limit reached.') !== false);
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }
}

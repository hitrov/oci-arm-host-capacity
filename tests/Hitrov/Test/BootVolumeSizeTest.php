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
        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/"code": "QuotaExceeded",\n\s+"message": ".*Service limit reached/');

        self::$config->setBootVolumeSizeInGBs(getenv('OCI_BOOT_VOLUME_SIZE_IN_GBS'));
        $instance = self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
    }
}

<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Exception\ApiCallException;
use Hitrov\OciApi;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\Test\Traits\LoadEnv;
use PHPUnit\Framework\TestCase;

class BootVolumeIdTest extends TestCase
{
    use DefaultConfig, LoadEnv;

    const ENV_FILENAME = '.env.boot_volume_id.test';

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->loadEnv();

        self::$config = $this->getDefaultConfig();
        self::$api = $this->getDefaultApi();
    }

    /**
     * @covers OciApi::createInstance
     * @covers \Hitrov\OciConfig::setBootVolumeId
     */
    public function testCreateInstance(): void
    {
        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessageMatches('/"code": "Conflict",\n\s+"message": "Volume ocid1\.bootvolume\.oc1\.phx\..*\scurrently attached/');

        putenv('OCI_BOOT_VOLUME_ID=ocid1.bootvolume.oc1.phx.abyhqljti2tk77lrczr3eoyh6pijlrsb7bgmjp3c52if52oezi7rj574rifa');

        self::$config->setBootVolumeId(getenv('OCI_BOOT_VOLUME_ID'));
        self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
    }
}

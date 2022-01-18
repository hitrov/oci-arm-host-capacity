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
        $exceptionThrown = false;
        try {
            self::$config->setBootVolumeId(getenv('OCI_BOOT_VOLUME_ID'));
            $instance = self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
        } catch(ApiCallException $e) {
            $response = $e->getMessage();
            $this->assertEquals(404, $e->getCode());
            $this->assertTrue(strpos($response, 'NotAuthorizedOrNotFound') !== false);
            $this->assertTrue(strpos($response, 'Available volume matching id') !== false);
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }
}

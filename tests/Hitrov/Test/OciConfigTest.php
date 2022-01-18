<?php

namespace Hitrov\Test;

use Hitrov\Exception\AvailabilityDomainRequiredException;
use Hitrov\Exception\BootVolumeSizeException;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\Test\Traits\LoadEnv;
use PHPUnit\Framework\TestCase;

class OciConfigTest extends TestCase
{
    use DefaultConfig, LoadEnv;

    const ENV_FILENAME = '.env.oci_config.test';

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        self::$config = $this->getDefaultConfig();
        self::$api = $this->getDefaultApi();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setSourceDetails
     */
    public function testSetSourceDetails(): void
    {
        $sourceDetailsExample = '{"hello": "world"}';
        self::$config->setSourceDetails($sourceDetailsExample);
        $sourceDetails = self::$config->getSourceDetails();

        $this->assertEquals($sourceDetailsExample, $sourceDetails);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeSizeInGBs
     */
    public function testSetBootVolumeSizeInGBs(): void
    {
        $bootVolumeSizeInGBs = '250';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);
        $sourceDetails = json_decode(self::$config->getSourceDetails(), true);

        $this->assertEquals('image', $sourceDetails['sourceType']);
        $this->assertEquals($bootVolumeSizeInGBs, $sourceDetails['bootVolumeSizeInGBs']);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testSetBootVolumeId(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);
        $sourceDetails = json_decode(self::$config->getSourceDetails(), true);

        $this->assertEquals('bootVolume', $sourceDetails['sourceType']);
        $this->assertEquals($bootVolumeId, $sourceDetails['bootVolumeId']);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testSetBootVolumeIdSetBootVolumeSizeInGBs(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);

        $bootVolumeSizeInGBs = '250';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);

        $this->expectException(BootVolumeSizeException::class);
        $this->expectExceptionMessage('OCI_BOOT_VOLUME_ID and OCI_BOOT_VOLUME_SIZE_IN_GBS cannot be used together');

        self::$config->getSourceDetails();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeSizeInGBs
     */
    public function testIncorrectBootVolumeSizeInGBs(): void
    {
        $bootVolumeSizeInGBs = 'hello';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);

        $this->expectException(BootVolumeSizeException::class);
        $this->expectExceptionMessage('OCI_BOOT_VOLUME_SIZE_IN_GBS must be numeric');

        self::$config->getSourceDetails();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testADRequiredForBootVolumeId(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);

        self::$config->availabilityDomains = '';

        $this->expectException(AvailabilityDomainRequiredException::class);
        $this->expectExceptionMessage('OCI_AVAILABILITY_DOMAIN must be specified as string if using OCI_BOOT_VOLUME_ID');

        self::$config->getSourceDetails();
    }
}

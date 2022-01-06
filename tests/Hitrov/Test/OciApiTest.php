<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Exception\ApiCallException;
use Hitrov\OciApi;
use Hitrov\OciConfig;
use PHPUnit\Framework\TestCase;

class OciApiTest extends TestCase
{
    const HAVE_INSTANCE = 'Already have an instance';

    private static OciApi $api;
    private static OciConfig $config;
    private static array $instances;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        self::$config = new OciConfig(
            getenv('OCI_REGION'),
            getenv('OCI_USER_ID'),
            getenv('OCI_TENANCY_ID'),
            getenv('OCI_KEY_FINGERPRINT'),
            getenv('OCI_PRIVATE_KEY_FILENAME'),
            getenv('OCI_AVAILABILITY_DOMAIN'),
            getenv('OCI_SUBNET_ID'),
            getenv('OCI_IMAGE_ID'),
            (int) getenv('OCI_OCPUS'),
            (int) getenv('OCI_MEMORY_IN_GBS'),
        );

        self::$api = new OciApi();
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetAvailabilityDomains(): void
    {
        $availabilityDomains = self::$api->getAvailabilityDomains(self::$config);

        $this->assertCount(3, $availabilityDomains);
        $this->assertCount(1, array_filter($availabilityDomains, function(array $availabilityDomain) {
            return $availabilityDomain['name'] === getenv('AD_ALWAYS_FREE');
        }));
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetInstances(): void
    {
        self::$instances = self::$api->getInstances(self::$config);

        $this->assertNotEmpty(self::$instances);
        $this->assertNotEmpty(array_filter(self::$instances, function(array $instance) {
            return $instance['availabilityDomain'] === getenv('AD_ALWAYS_FREE');
        }));
    }

    /**
     * @covers OciApi::checkExistingInstances
     */
    public function testCheckExistingInstances(): void
    {
        $existingInstancesErrorMessage = self::$api->checkExistingInstances(
            self::$config,
            self::$instances,
            getenv('OCI_SHAPE'),
            (int) getenv('OCI_MAX_INSTANCES'),
        );

        $this->assertEquals(0, strpos($existingInstancesErrorMessage, self::HAVE_INSTANCE));
    }

    /**
     * @covers OciApi::createInstance
     */
    public function testCreateInstance(): void
    {
        $config = clone self::$config;
        $config->imageId = getenv('TEST_IMAGE_ID');
        $config->subnetId = getenv('TEST_SUBNET_ID');
        $config->ocpus = 1;
        $config->memoryInGBs = 1;

        $exceptionThrown = false;
        try {
            $instance = self::$api->createInstance($config, 'VM.Standard.E2.1.Micro', getenv('OCI_SSH_PUBLIC_KEY'), getenv('AD_ALWAYS_FREE'));
        } catch(ApiCallException $e) {
            $response = $e->getMessage();
            $this->assertEquals(400, $e->getCode());
            $this->assertTrue(strpos($response, 'LimitExceeded') !== false);
            $this->assertTrue(strpos($response, 'The following service limits were exceeded') !== false);
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }
}

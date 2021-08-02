<?php


namespace Hitrov\Test;


use Hitrov\OciApi;
use Hitrov\OciConfig;
use PHPUnit\Framework\TestCase;

class OciApiTest extends TestCase
{
    const HAVE_INSTANCE = 'Already have an instance';

    private static OciConfig $config;
    private static array $listResponse;
    private static string $existingInstances;

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
            getenv('OCI_OCPUS'),
            getenv('OCI_MEMORY_IN_GBS'),
        );
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetInstances(): void
    {
        $api = new OciApi();

        [ $listResponse, $listError, $listInfo ] = $api->getInstances(self::$config);

        self::$listResponse = json_decode($listResponse, true);

        $this->assertEmpty(json_last_error());
        $this->assertNotEmpty(self::$listResponse);
        $this->assertEquals(getenv('OCI_AVAILABILITY_DOMAIN'), self::$listResponse[0]['availabilityDomain']);
    }

    /**
     * @covers OciApi::checkExistingInstances
     */
    public function testCheckExistingInstances(): void
    {
        $api = new OciApi();

        self::$existingInstances = $api->checkExistingInstances(self::$config, self::$listResponse, getenv('OCI_SHAPE'), getenv('OCI_MAX_INSTANCES'));
        $this->assertEquals(0, strpos(self::$existingInstances, self::HAVE_INSTANCE));
    }

    /**
     * @covers OciApi::createInstance
     */
    public function testCreateInstance(): void
    {
        if (self::$existingInstances) {
            $this->markTestSkipped(self::HAVE_INSTANCE);
        }

        $api = new OciApi();

        $result = $api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'));

        $this->assertNotEmpty($result);
        $this->assertEquals(getenv('OCI_AVAILABILITY_DOMAIN'), $result[0]['availabilityDomain']);
        $this->assertEquals(getenv('OCI_TENANCY_ID'), $result[0]['compartmentId']);
        $this->assertEquals(getenv('OCI_IMAGE_ID'), $result[0]['imageId']);
        $this->assertEquals(getenv('OCI_SHAPE'), $result[0]['shape']);
    }
}

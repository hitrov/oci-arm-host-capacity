<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\OciApi;
use Hitrov\OciConfig;
use PHPUnit\Framework\TestCase;

class OciApiTest extends TestCase
{
    const HAVE_INSTANCE = 'Already have an instance';

    private static OciApi $api;
    private static OciConfig $config;
    private static array $listResponse;
    private static string $existingInstancesErrorMessage;
    private static array $existingInstances;

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
    public function testGetInstances(): void
    {
        [ $listResponse, $listError, $listInfo ] = self::$api->getInstances(self::$config);

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
        self::$existingInstancesErrorMessage = self::$api->checkExistingInstances(
            self::$config,
            self::$listResponse,
            getenv('OCI_SHAPE'),
            (int) getenv('OCI_MAX_INSTANCES'),
        );
        self::$existingInstances = self::$api->getExistingInstances();
        $this->assertEquals(0, strpos(self::$existingInstancesErrorMessage, self::HAVE_INSTANCE));
    }

    /**
     * @covers OciApi::createInstance
     */
    public function testCreateInstance(): void
    {
        [ $response, $listError, $listInfo ] = self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'));
        $responseArray = json_decode($response, true);
        $this->assertNotEmpty($responseArray);

        $existingInstancesNumber = count(self::$existingInstances);
        $maxInstances = (int) getenv('OCI_MAX_INSTANCES');

        if (self::$existingInstancesErrorMessage && $existingInstancesNumber < $maxInstances) {
            $this->assertEquals(getenv('OCI_AVAILABILITY_DOMAIN'), $responseArray['availabilityDomain']);
            $this->assertEquals(getenv('OCI_TENANCY_ID'), $responseArray['compartmentId']);
            $this->assertEquals(getenv('OCI_IMAGE_ID'), $responseArray['imageId']);
            $this->assertEquals(getenv('OCI_SHAPE'), $responseArray['shape']);
        } else {
            $this->assertEquals('LimitExceeded', $responseArray['code']);
            $this->assertTrue(strpos($responseArray['message'], 'The following service limits were exceeded') === 0);
        }
    }
}

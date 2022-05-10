<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Exception\ApiCallException;
use Hitrov\OciApi;
use Hitrov\Test\Traits\DefaultConfig;
use PHPUnit\Framework\TestCase;

class OciApiTest extends TestCase
{
    use DefaultConfig;

    const HAVE_INSTANCE = 'Already have an instance';

    private static array $instances;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->setEnv();

        self::$config = $this->getDefaultConfig();
        self::$api = $this->getDefaultApi();
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetAvailabilityDomains(): void
    {
        $availabilityDomains = self::$api->getAvailabilityDomains(self::$config);

        $this->assertCount(3, $availabilityDomains);
        $this->assertCount(1, array_filter($availabilityDomains, function(array $availabilityDomain) {
            return $availabilityDomain['name'] === getenv('OCI_AVAILABILITY_DOMAIN');
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
            return $instance['availabilityDomain'] === getenv('OCI_AVAILABILITY_DOMAIN');
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
        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/"code": "LimitExceeded",\n\s+"message": "The following service limits were exceeded:.*Request a service limit increase from the service limits page in the console/');

        self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
    }

    protected function setEnv(): void
    {
        putenv('OCI_SHAPE=VM.Standard.E2.1.Micro');
        putenv('OCI_OCPUS=1');
        putenv('OCI_MEMORY_IN_GBS=1');
        putenv('OCI_AVAILABILITY_DOMAIN=FeVO:EU-FRANKFURT-1-AD-2');
        putenv('OCI_IMAGE_ID=ocid1.image.oc1.eu-frankfurt-1.aaaaaaaado5423wtoss2ogoj2xpr4wssqsfy7yeafyekiywhuep7wnvwpvuq');
        putenv('OCI_SUBNET_ID=ocid1.subnet.oc1.eu-frankfurt-1.aaaaaaaaahbb6t2jetpfmfi5kn7ypi4w6pn3qt6s3k4xzvwxmjt3tjmv3faq');
    }
}

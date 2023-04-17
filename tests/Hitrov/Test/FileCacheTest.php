<?php

namespace Hitrov\Test;

use Hitrov\FileCache;
use Hitrov\Test\Traits\DefaultConfig;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    const CONFIG_MD5 = '022f4537505321f3efefe7016082508f';

    use DefaultConfig;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        if (file_exists($this->getCacheFilename())) {
            unlink($this->getCacheFilename());
        }
    }

    public function testGetCacheKey(): void
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $this->assertEquals(
            self::CONFIG_MD5,
            $cache->getCacheKey('foo'),
        );
    }

    public function testCacheFileCreated(): void
    {
        $config = $this->getDefaultConfig();
        $api = $this->getDefaultApi();

        $api->setCache(new FileCache($config));

        $this->assertTrue(
            file_exists(sprintf('%s/%s', getcwd(), 'oci_cache.json')),
        );
    }

    public function testAddsCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $expected = <<<EOD
{
    "foo": {
        "022f4537505321f3efefe7016082508f": [
            1,
            "one"
        ]
    }
}
EOD;

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testUpdatesCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $existingCache = <<<EOD
{
    "foo": {
        "0c4b5682ece1704df5bf11d71fa55177": [
            1,
            "one"
        ]
    }
}
EOD;

        file_put_contents($this->getCacheFilename(), $existingCache);

        $cache->add([2, 'two'], 'bar');

        $expected = <<<EOD
{
    "foo": {
        "0c4b5682ece1704df5bf11d71fa55177": [
            1,
            "one"
        ]
    },
    "bar": {
        "022f4537505321f3efefe7016082508f": [
            2,
            "two"
        ]
    }
}
EOD;

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testUpdatesWithDifferentConfig()
    {
        $config = $this->getDefaultConfig();
        $config->bootVolumeId = 'baz';
        $cache = new FileCache($config);

        $existingCache = <<<EOD
{
    "foo": {
        "0c4b5682ece1704df5bf11d71fa55177": [
            1,
            "one"
        ]
    }
}
EOD;

        file_put_contents($this->getCacheFilename(), $existingCache);

        $cache->add([11, 'eleven'], 'foo');

        $expected = <<<EOD
{
    "foo": {
        "0c4b5682ece1704df5bf11d71fa55177": [
            1,
            "one"
        ],
        "c9788a1da7c524be16aafc4042e2d9e4": [
            11,
            "eleven"
        ]
    }
}
EOD;

        $this->assertEquals(
            $expected,
            file_get_contents($this->getCacheFilename()),
        );
    }

    public function testGet()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $this->assertEquals(
            [1, 'one'],
            $cache->get('foo'),
        );
    }

    private function getCacheFilename(): string
    {
        return sprintf('%s/%s', getcwd(), 'oci_cache.json');
    }
}
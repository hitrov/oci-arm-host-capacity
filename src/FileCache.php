<?php

namespace Hitrov;

use Hitrov\Interfaces\CacheInterface;

class FileCache implements CacheInterface
{
    private const CACHE_FILENAME = 'oci_cache.json';

    private OciConfig $config;

    public function __construct(OciConfig $config)
    {
        $this->config = $config;

        $this->createCacheFile();
    }

    /**
     * @throws \JsonException
     */
    public function add($data, string $method): void
    {
        $cache = $this->getCache();
        $cacheKey = $this->getCacheKey($method);

        $cache[$method][$cacheKey] = $data;

        file_put_contents(
            $this->getCacheFilename(),
            json_encode($cache, JSON_PRETTY_PRINT),
        );
    }

    /**
     * @return mixed
     * @throws \JsonException
     */
    public function get(string $method)
    {
        $cache = $this->getCache();
        if (!isset($cache[$method])) {
            return null;
        }
        $cacheKey = $this->getCacheKey($method);
        if (!isset($cache[$method][$cacheKey])) {
            return null;
        }

        return $cache[$method][$cacheKey];
    }

    public function getCacheKey(string $method): string
    {
        return md5(json_encode($this->config));
    }

    private function getCacheFilename(): string
    {
        return sprintf('%s/%s', getcwd(), self::CACHE_FILENAME);
    }

    /**
     * @throws \JsonException
     */
    private function getCache(): array
    {
        $cacheContents = file_get_contents($this->getCacheFilename());

        return json_decode($cacheContents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function createCacheFile(): void
    {
        $filename = $this->getCacheFilename();
        if (file_exists($filename)) {
            return;
        }

        file_put_contents($filename, '{}');
    }
}

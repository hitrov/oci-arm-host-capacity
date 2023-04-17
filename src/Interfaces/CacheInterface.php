<?php

namespace Hitrov\Interfaces;

interface CacheInterface
{
    public function add($data, string $method): void;

    /**
     * @return mixed
     */
    public function get(string $method);

    public function getCacheKey(string $method): string;
}

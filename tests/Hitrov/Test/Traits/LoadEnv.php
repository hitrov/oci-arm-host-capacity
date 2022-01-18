<?php

namespace Hitrov\Test\Traits;

use Dotenv\Dotenv;

trait LoadEnv
{
    protected function loadEnv(): void
    {
        $envFileDir = getcwd() . '/tests/resources';
        $dotenv = Dotenv::createUnsafeMutable($envFileDir, static::ENV_FILENAME);
        $dotenv->safeLoad();
    }
}

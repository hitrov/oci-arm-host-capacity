<?php

namespace Hitrov;

use Hitrov\Interfaces\TooManyRequestsWaiterInterface;

class TooManyRequestsWaiter implements TooManyRequestsWaiterInterface
{
    private const WAITER_FILENAME = 'too_many_requests_waiter.txt';
    private int $waitSeconds;

    public function __construct(int $waitTime)
    {
        $this->waitSeconds = $waitTime;
        if ($this->fileExists()) {
            return;
        }

        file_put_contents($this->getFilename(), '');
    }

    public function isTooEarly(): bool
    {
        if (!$this->fileExists()) {
            return false;
        }

        return time() < (int) file_get_contents($this->getFilename());
    }

    public function isConfigured(): bool
    {
        return $this->waitSeconds > 0;
    }

    public function enable(): void
    {
        file_put_contents($this->getFilename(), time() + $this->waitSeconds);
    }

    public function remove(): void
    {
        if ($this->fileExists()) {
            unlink($this->getFilename());
        }
    }

    public function secondsRemaining(): int
    {
        if (!$this->fileExists()) {
            return 0;
        }

        return (int) file_get_contents($this->getFilename()) - time();
    }

    private function getFilename(): string
    {
        return sprintf('%s/%s', getcwd(), self::WAITER_FILENAME);
    }

    private function fileExists(): bool
    {
        return file_exists($this->getFilename());
    }
}

<?php

namespace Hitrov\Test;

use Hitrov\TooManyRequestsWaiter;
use PHPUnit\Framework\TestCase;

class TooManyRequestsWaiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFile();
    }

    public function testFileCreated(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $this->assertTrue(
            file_exists($this->getFilename()),
        );
    }

    public function testIsConfigured(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $this->assertTrue($waiter->isConfigured());
    }

    public function testIsNotConfiguredWhenEmptyEnvVar(): void
    {
        $waiter = new TooManyRequestsWaiter((int) null);

        $this->assertFalse($waiter->isConfigured());
    }

    public function testIsNotConfiguredWhenFalseEnvVar(): void
    {
        $waiter = new TooManyRequestsWaiter((int) false);

        $this->assertFalse($waiter->isConfigured());
    }

    public function testEnable(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $time = time();
        $waiter->enable();

        $this->assertTrue($time + 10 >= file_get_contents($this->getFilename()));
    }

    public function testEnableWhenFileDoesNotExists(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $this->deleteFile();
        $waiter->enable();

        $this->assertTrue(file_exists($this->getFilename()));
    }

    public function testIsTooEarlyWhenEnabled(): void
    {
        $waiter = new TooManyRequestsWaiter(10);
        $waiter->enable();

        $this->assertTrue($waiter->isTooEarly());
    }

    public function testIsTooEarlyWhenFileExists(): void
    {
        $waiter = new TooManyRequestsWaiter(0);

        file_put_contents($this->getFilename(), time() + 10);

        $this->assertTrue($waiter->isTooEarly());
    }

    public function testRemove(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $waiter->remove();

        $this->assertFalse(file_exists($this->getFilename()));
    }

    public function testRemoveWhenFileDoesNotExist(): void
    {
        $waiter = new TooManyRequestsWaiter(10);
        $this->deleteFile();

        $waiter->remove();

        $this->assertFalse(file_exists($this->getFilename()));
    }

    public function testIsTooEarlyAfterRemove(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $waiter->remove();

        $this->assertFalse($waiter->isTooEarly());
    }

    public function testSecondsRemaining(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $waiter->enable();

        $this->assertEquals(10, $waiter->secondsRemaining());
    }

    public function testSecondsRemainingWithFileContents(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        file_put_contents($this->getFilename(), time() + 10);

        $this->assertEquals(10, $waiter->secondsRemaining());
    }

    public function testSecondsRemainingWhenFileDoesNotExist(): void
    {
        $waiter = new TooManyRequestsWaiter(10);

        $this->deleteFile();

        $this->assertEquals(0, $waiter->secondsRemaining());
    }

    private function getFilename(): string
    {
        return sprintf('%s/%s', getcwd(), 'too_many_requests_waiter.txt');
    }

    private function deleteFile(): void
    {
        if (file_exists($this->getFilename())) {
            unlink($this->getFilename());
        }
    }
}
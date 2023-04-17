<?php

namespace Hitrov\Test;

use Hitrov\Exception\ApiCallException;
use Hitrov\Exception\TooManyRequestsWaiterException;
use Hitrov\OciApi;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\TooManyRequestsWaiter;
use PHPUnit\Framework\TestCase;

class OciApiCreateInstanceWithWaiterTest extends TestCase
{
    use DefaultConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteFile();
    }

    public function testIsTooEarly(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->never())
            ->method('call');

        $waiter = new TooManyRequestsWaiter(10);
        $waiter->enable();

        $mock->setWaiter($waiter);

        $this->expectException(TooManyRequestsWaiterException::class);
        $this->expectExceptionMessage('Will retry after 10 seconds');
        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testWhenNotSet(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willReturn([]);

        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testWhenNotConfigured(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willReturn([]);

        $waiter = new TooManyRequestsWaiter(0);

        $mock->setWaiter($waiter);

        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testTimePassedFileRemoved(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willReturn([]);

        $waiter = new TooManyRequestsWaiter(10);
        $waiter->enable();
        $mock->setWaiter($waiter);

        file_put_contents($this->getFilename(), time() - 10);

        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
        $this->assertFalse(file_exists($this->getFilename()));
    }

    public function testWaiterNotEnabledForOtherErrors(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willThrowException(new ApiCallException('foo'));

        $waiter = new TooManyRequestsWaiter(10);
        $mock->setWaiter($waiter);

        $this->expectException(ApiCallException::class);
        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testTooManyRequestsHttpCode(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willThrowException(new ApiCallException('foo', 429))
        ;

        $waiter = new TooManyRequestsWaiter(10);
        $mock->setWaiter($waiter);

        $this->expectException(TooManyRequestsWaiterException::class);
        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testTooManyRequestsMessage(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willThrowException(new ApiCallException('TooManyRequests'))
        ;

        $waiter = new TooManyRequestsWaiter(10);
        $mock->setWaiter($waiter);

        $this->expectException(TooManyRequestsWaiterException::class);
        $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
    }

    public function testSecondsRemainingAfterTooManyRequests(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willThrowException(new ApiCallException('TooManyRequests', 429));

        $waiter = new TooManyRequestsWaiter(10);
        $mock->setWaiter($waiter);

        try {
            $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
        } catch (TooManyRequestsWaiterException $e) {

        } finally {
            $this->assertEquals(10, $waiter->secondsRemaining());
        }
    }

    public function testFileContentsAfterTooManyRequests(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willThrowException(new ApiCallException('TooManyRequests', 429));

        $waiter = new TooManyRequestsWaiter(10);
        $mock->setWaiter($waiter);

        try {
            $mock->createInstance($this->getDefaultConfig(), 'foo', 'ssh key', 'ad');
        } catch (TooManyRequestsWaiterException $e) {

        } finally {
            $this->assertEquals(time() + 10, file_get_contents($this->getFilename()));
        }
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
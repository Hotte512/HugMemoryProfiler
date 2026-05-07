<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Tests\Unit\Subscriber;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Swp\MemoryProfiler\Subscriber\MemoryProfilerSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class MemoryProfilerSubscriberTest extends TestCase
{
    public function testSubscribesToCorrectEvents(): void
    {
        $events = MemoryProfilerSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
        self::assertArrayHasKey(KernelEvents::TERMINATE, $events);
    }

    public function testDisabledSubscriberDoesNotInsert(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('insert');

        $subscriber = new MemoryProfilerSubscriber($connection, false);
        $subscriber->onRequest($this->makeRequestEvent('/'));
        $subscriber->onTerminate($this->makeTerminateEvent('/', 200));
    }

    public function testStorefrontContextClassification(): void
    {
        $captured = $this->captureInsertData('/some/product');
        self::assertSame('STORE', $captured['context']);
    }

    public function testApiContextClassification(): void
    {
        $captured = $this->captureInsertData('/api/foo/bar');
        self::assertSame('API', $captured['context']);
    }

    public function testAdminContextClassification(): void
    {
        $captured = $this->captureInsertData('/admin#/sw/dashboard/index');
        self::assertSame('ADMIN', $captured['context']);
    }

    public function testInsertExceptionDoesNotBubble(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('insert')->willThrowException(new \RuntimeException('DB down'));

        $subscriber = new MemoryProfilerSubscriber($connection, true);
        $subscriber->onRequest($this->makeRequestEvent('/'));

        // Must not throw
        $subscriber->onTerminate($this->makeTerminateEvent('/', 200));

        $this->expectNotToPerformAssertions();
    }

    public function testSubRequestIsIgnored(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('insert');

        $kernel  = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $event   = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $subscriber = new MemoryProfilerSubscriber($connection, true);
        $subscriber->onRequest($event);
        // No onTerminate insert because startTime stays 0
        $subscriber->onTerminate($this->makeTerminateEvent('/', 200));
    }

    /**
     * @return array<string, mixed>
     */
    private function captureInsertData(string $path): array
    {
        $captured  = [];
        $connection = $this->createMock(Connection::class);
        $connection->method('insert')->willReturnCallback(
            function (string $table, array $data) use (&$captured): int {
                $captured = $data;
                return 1;
            }
        );

        $subscriber = new MemoryProfilerSubscriber($connection, true);
        $subscriber->onRequest($this->makeRequestEvent($path));
        $subscriber->onTerminate($this->makeTerminateEvent($path, 200));

        return $captured;
    }

    private function makeRequestEvent(string $path): RequestEvent
    {
        $kernel  = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function makeTerminateEvent(string $path, int $status): TerminateEvent
    {
        $kernel   = $this->createMock(HttpKernelInterface::class);
        $request  = Request::create($path);
        $response = new Response('', $status);
        return new TerminateEvent($kernel, $request, $response);
    }
}

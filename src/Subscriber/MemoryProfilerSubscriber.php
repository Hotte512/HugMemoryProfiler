<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MemoryProfilerSubscriber implements EventSubscriberInterface
{
    private const LOG_FILE = '/tmp/sw6-memory.log';

    private float $startTime = 0.0;
    private int $startMemory = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST   => ['onRequest', 9999],
            KernelEvents::TERMINATE => ['onTerminate', -9999],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->startTime   = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();

        $peakMb     = memory_get_peak_usage(true) / 1048576;
        $deltaMb    = (memory_get_usage(true) - $this->startMemory) / 1048576;
        $durationMs = (microtime(true) - $this->startTime) * 1000;

        $context = match (true) {
            str_starts_with($request->getPathInfo(), '/api')   => 'API',
            str_starts_with($request->getPathInfo(), '/admin') => 'ADMIN',
            default                                            => 'STORE',
        };

        $line = sprintf(
            "%s | %-5s | %3d | peak=%6.1fMB | delta=%6.1fMB | %5dms | %s %s%s\n",
            date('Y-m-d H:i:s'),
            $context,
            $event->getResponse()->getStatusCode(),
            $peakMb,
            $deltaMb,
            $durationMs,
            $request->getMethod(),
            $request->getPathInfo(),
            $request->getQueryString() ? '?' . $request->getQueryString() : ''
        );

        @file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}

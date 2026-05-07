<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MemoryProfilerSubscriber implements EventSubscriberInterface
{
    private float $startTime = 0.0;
    private int $startMemory = 0;

    public function __construct(
        private readonly Connection $connection,
        private readonly bool $enabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST   => ['onRequest', 9999],
            KernelEvents::TERMINATE => ['onTerminate', -9999],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }
        $this->startTime   = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (!$this->enabled || $this->startTime === 0.0) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        $context = match (true) {
            str_starts_with($path, '/api')   => 'API',
            str_starts_with($path, '/admin') => 'ADMIN',
            default                          => 'STORE',
        };

        $peakMb     = round(memory_get_peak_usage(true) / 1048576, 2);
        $deltaMb    = round((memory_get_usage(true) - $this->startMemory) / 1048576, 2);
        $durationMs = (int) round((microtime(true) - $this->startTime) * 1000);

        // Schutz gegen unrealistische Werte (z.B. nach FPM-Worker-Recycling)
        if ($durationMs < 0 || $durationMs > 600000) {
            $durationMs = 0;
        }

        try {
            $this->connection->insert('swp_memory_profile', [
                'id'              => Uuid::randomBytes(),
                'context'         => $context,
                'method'          => substr($request->getMethod(), 0, 8),
                'path'            => substr($path, 0, 2048),
                'query_string'    => $request->getQueryString(),
                'status_code'     => $event->getResponse()->getStatusCode(),
                'peak_memory_mb'  => $peakMb,
                'delta_memory_mb' => $deltaMb,
                'duration_ms'     => $durationMs,
                'created_at'      => (new \DateTime())->format('Y-m-d H:i:s.v'),
            ]);
        } catch (\Throwable) {
            // niemals den Request kaputtmachen wegen Profiling
        }

        // State zurücksetzen für FPM-Worker-Recycling
        $this->startTime   = 0.0;
        $this->startMemory = 0;
    }
}

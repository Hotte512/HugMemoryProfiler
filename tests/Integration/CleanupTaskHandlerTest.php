<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Tests\Integration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swp\MemoryProfiler\ScheduledTask\CleanupTaskHandler;

class CleanupTaskHandlerTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = self::getContainer()->get(Connection::class);
        $this->connection->executeStatement('TRUNCATE TABLE swp_memory_profile');
    }

    public function testCleanupRemovesOldEntries(): void
    {
        // Insert 3 old + 2 fresh entries
        $this->insertEntry('-40 days');
        $this->insertEntry('-35 days');
        $this->insertEntry('-31 days');
        $this->insertEntry('-1 days');
        $this->insertEntry('now');

        $handler = self::getContainer()->get(CleanupTaskHandler::class);
        $handler->run();

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM swp_memory_profile'
        );
        self::assertSame(2, $count, 'Expected only 2 fresh entries to remain');
    }

    private function insertEntry(string $relativeTime): void
    {
        $this->connection->insert('swp_memory_profile', [
            'id'              => random_bytes(16),
            'context'         => 'STORE',
            'method'          => 'GET',
            'path'            => '/',
            'query_string'    => null,
            'status_code'     => 200,
            'peak_memory_mb'  => 50.0,
            'delta_memory_mb' => 10.0,
            'duration_ms'     => 100,
            'created_at'      => (new \DateTime($relativeTime))->format('Y-m-d H:i:s.v'),
        ]);
    }
}

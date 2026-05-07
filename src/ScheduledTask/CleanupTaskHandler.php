<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\ScheduledTask;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\Scheduled\ScheduledTaskRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CleanupTask::class)]
class CleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        $scheduledTaskRepository,
        private readonly Connection $connection,
        private readonly int $retentionDays = 30,
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [CleanupTask::class];
    }

    public function run(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM swp_memory_profile WHERE created_at < :cutoff',
            ['cutoff' => (new \DateTime())->modify("-{$this->retentionDays} days")->format('Y-m-d H:i:s')]
        );
    }
}

<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'swp.memory_profiler.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return 86400; // täglich
    }
}

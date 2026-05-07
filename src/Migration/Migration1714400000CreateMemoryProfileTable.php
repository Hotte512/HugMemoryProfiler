<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1714400000CreateMemoryProfileTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1714400000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `swp_memory_profile` (
    `id` BINARY(16) NOT NULL,
    `context` VARCHAR(16) NOT NULL,
    `method` VARCHAR(8) NOT NULL,
    `path` VARCHAR(2048) NOT NULL,
    `query_string` TEXT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL,
    `peak_memory_mb` DECIMAL(8,2) NOT NULL,
    `delta_memory_mb` DECIMAL(8,2) NOT NULL,
    `duration_ms` INT UNSIGNED NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx.created_at` (`created_at`),
    INDEX `idx.context_peak` (`context`, `peak_memory_mb`),
    INDEX `idx.peak` (`peak_memory_mb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

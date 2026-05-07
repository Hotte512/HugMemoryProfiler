<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'swp:memory:clear', description: 'Clear memory profile data')]
class ClearCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('older-than', null, InputOption::VALUE_REQUIRED, 'Delete entries older than N days (default: all)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $olderThan = $input->getOption('older-than');

        if ($olderThan !== null) {
            $cutoff = (new \DateTime())->modify("-{$olderThan} days")->format('Y-m-d H:i:s');
            $count  = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM swp_memory_profile WHERE created_at < :cutoff',
                ['cutoff' => $cutoff]
            );

            if (!$input->getOption('force') && !$io->confirm("$count Einträge älter als $olderThan Tage löschen?", false)) {
                return Command::SUCCESS;
            }

            $this->connection->executeStatement(
                'DELETE FROM swp_memory_profile WHERE created_at < :cutoff',
                ['cutoff' => $cutoff]
            );
            $io->success("$count Einträge gelöscht.");
        } else {
            $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM swp_memory_profile');

            if (!$input->getOption('force') && !$io->confirm("ALLE $count Einträge löschen?", false)) {
                return Command::SUCCESS;
            }

            $this->connection->executeStatement('TRUNCATE TABLE swp_memory_profile');
            $io->success("$count Einträge gelöscht (Tabelle truncated).");
        }

        return Command::SUCCESS;
    }
}

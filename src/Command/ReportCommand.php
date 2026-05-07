<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'swp:memory:report', description: 'Show memory profiling report')]
class ReportCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Top N entries', '20')
            ->addOption('context', 'c', InputOption::VALUE_REQUIRED, 'Filter by context (STORE|ADMIN|API)')
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'Only entries of last N hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $where  = ['1=1'];
        $params = [];

        if ($ctx = $input->getOption('context')) {
            $where[]          = 'context = :ctx';
            $params['ctx']    = strtoupper($ctx);
        }
        if ($hours = $input->getOption('hours')) {
            $where[]            = 'created_at >= :since';
            $params['since']    = (new \DateTime())->modify("-{$hours} hours")->format('Y-m-d H:i:s');
        }
        $whereSql = implode(' AND ', $where);

        // Stats pro Kontext
        $io->section('Statistik pro Kontext');
        $stats = $this->connection->fetchAllAssociative("
            SELECT context,
                   COUNT(*) AS cnt,
                   ROUND(AVG(peak_memory_mb), 1) AS avg_mb,
                   MAX(peak_memory_mb) AS peak_mb,
                   ROUND(AVG(duration_ms)) AS avg_ms,
                   MAX(duration_ms) AS max_ms
            FROM swp_memory_profile
            WHERE $whereSql
            GROUP BY context
            ORDER BY peak_mb DESC
        ", $params);

        if (empty($stats)) {
            $io->warning('Keine Daten gefunden.');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Kontext', 'Anzahl', 'Avg MB', 'Peak MB', 'Avg ms', 'Max ms']);
        foreach ($stats as $r) {
            $table->addRow([$r['context'], $r['cnt'], $r['avg_mb'], $r['peak_mb'], $r['avg_ms'], $r['max_ms']]);
        }
        $table->render();

        // Top N
        $limit = (int) $input->getOption('limit');
        $io->section("Top {$limit} Memory-Hogs");

        $top = $this->connection->fetchAllAssociative("
            SELECT context, method, path, status_code, peak_memory_mb, duration_ms, created_at
            FROM swp_memory_profile
            WHERE $whereSql
            ORDER BY peak_memory_mb DESC
            LIMIT $limit
        ", $params);

        $table = new Table($output);
        $table->setHeaders(['Zeit', 'Ctx', 'Status', 'Peak', 'ms', 'Request']);
        foreach ($top as $r) {
            $reqStr = $r['method'] . ' ' . substr($r['path'], 0, 80);
            $table->addRow([
                $r['created_at'],
                $r['context'],
                $r['status_code'],
                $r['peak_memory_mb'] . ' MB',
                $r['duration_ms'],
                $reqStr,
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}

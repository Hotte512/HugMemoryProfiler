<?php declare(strict_types=1);

namespace Swp\MemoryProfiler\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class MemoryProfileController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route(path: '/api/_action/swp-memory-profiler/stats', name: 'api.swp_memory_profiler.stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        [$where, $params] = $this->buildFilter($request);

        $byContext = $this->connection->fetchAllAssociative("
            SELECT context,
                   COUNT(*) AS cnt,
                   ROUND(AVG(peak_memory_mb), 1) AS avg_mb,
                   MAX(peak_memory_mb) AS peak_mb,
                   ROUND(AVG(duration_ms)) AS avg_ms,
                   MAX(duration_ms) AS max_ms
            FROM swp_memory_profile
            WHERE $where
            GROUP BY context
            ORDER BY peak_mb DESC
        ", $params);

        $totals = $this->connection->fetchAssociative("
            SELECT COUNT(*) AS total,
                   ROUND(AVG(peak_memory_mb), 1) AS avg_mb,
                   MAX(peak_memory_mb) AS peak_mb
            FROM swp_memory_profile
            WHERE $where
        ", $params);

        $oldest = $this->connection->fetchOne(
            "SELECT MIN(created_at) FROM swp_memory_profile WHERE $where", $params
        );
        $newest = $this->connection->fetchOne(
            "SELECT MAX(created_at) FROM swp_memory_profile WHERE $where", $params
        );

        return new JsonResponse([
            'totals'    => $totals,
            'byContext' => $byContext,
            'range'     => ['oldest' => $oldest, 'newest' => $newest],
        ]);
    }

    #[Route(path: '/api/_action/swp-memory-profiler/list', name: 'api.swp_memory_profiler.list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        [$where, $params] = $this->buildFilter($request);

        $limit  = min(500, max(1, (int) $request->query->get('limit', 50)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $sort   = $request->query->get('sort', 'peak_memory_mb');
        $order  = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSort = ['peak_memory_mb', 'duration_ms', 'created_at', 'status_code'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'peak_memory_mb';
        }

        $rows = $this->connection->fetchAllAssociative("
            SELECT LOWER(HEX(id)) AS id, context, method, path, query_string,
                   status_code, peak_memory_mb, delta_memory_mb, duration_ms, created_at
            FROM swp_memory_profile
            WHERE $where
            ORDER BY $sort $order
            LIMIT $limit OFFSET $offset
        ", $params);

        $total = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM swp_memory_profile WHERE $where", $params
        );

        return new JsonResponse(['data' => $rows, 'total' => $total]);
    }

    #[Route(path: '/api/_action/swp-memory-profiler/clear', name: 'api.swp_memory_profiler.clear', methods: ['POST'])]
    public function clear(Request $request): JsonResponse
    {
        $olderThan = $request->request->get('olderThanDays');

        if ($olderThan !== null && $olderThan !== '') {
            $cutoff  = (new \DateTime())->modify('-' . (int) $olderThan . ' days')->format('Y-m-d H:i:s');
            $deleted = $this->connection->executeStatement(
                'DELETE FROM swp_memory_profile WHERE created_at < :cutoff',
                ['cutoff' => $cutoff]
            );
        } else {
            $deleted = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM swp_memory_profile');
            $this->connection->executeStatement('TRUNCATE TABLE swp_memory_profile');
        }

        return new JsonResponse(['deleted' => $deleted]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilter(Request $request): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($ctx = $request->query->get('context')) {
            $where[]       = 'context = :ctx';
            $params['ctx'] = strtoupper($ctx);
        }
        if ($status = $request->query->get('status')) {
            $where[]          = 'status_code = :status';
            $params['status'] = (int) $status;
        }
        if ($route = $request->query->get('route')) {
            $where[]         = 'path LIKE :route';
            $params['route'] = '%' . $route . '%';
        }
        if ($hours = $request->query->get('hours')) {
            $where[]         = 'created_at >= :since';
            $params['since'] = (new \DateTime())->modify('-' . (int) $hours . ' hours')->format('Y-m-d H:i:s');
        }
        if ($minPeak = $request->query->get('minPeak')) {
            $where[]            = 'peak_memory_mb >= :minPeak';
            $params['minPeak']  = (float) $minPeak;
        }

        return [implode(' AND ', $where), $params];
    }
}

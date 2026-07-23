<?php

namespace App\Domain\Goods;

use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Collection;

/**
 * Read-side goods access against external product DB.
 * SQL is intentionally simple and adaptable — adjust table/column names
 * to match your live schema (inspect via information_schema / Delphi queries).
 */
class GoodsRepository
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * Heuristic search: tries common table names used by legacy Product.
     * Returns empty collection if schema is unavailable.
     */
    public function search(?string $term = null, int $limit = 50, int $offset = 0): Collection
    {
        if (! $this->query->tableExists('goods') && ! $this->query->tableExists('tov')) {
            return collect();
        }

        $table = $this->query->tableExists('goods') ? 'goods' : 'tov';
        $term = trim((string) $term);

        if ($term === '') {
            return $this->query->select(
                "SELECT * FROM {$table} ORDER BY 1 DESC LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        }

        // Best-effort filter on common columns; ignore missing columns at runtime via try.
        try {
            return $this->query->select(
                "SELECT * FROM {$table}
                 WHERE CAST(id AS CHAR) LIKE ?
                    OR name LIKE ?
                 ORDER BY name
                 LIMIT ? OFFSET ?",
                ['%'.$term.'%', '%'.$term.'%', $limit, $offset]
            );
        } catch (\Throwable) {
            return $this->query->select(
                "SELECT * FROM {$table} LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        }
    }

    public function find(int $id): ?object
    {
        foreach (['goods', 'tov'] as $table) {
            if (! $this->query->tableExists($table)) {
                continue;
            }
            try {
                $row = $this->query->selectOne("SELECT * FROM {$table} WHERE id = ? LIMIT 1", [$id]);
                if ($row) {
                    return $row;
                }
            } catch (\Throwable) {
                // try next
            }
        }

        return null;
    }

    public function available(): bool
    {
        return $this->query->tableExists('goods') || $this->query->tableExists('tov');
    }
}

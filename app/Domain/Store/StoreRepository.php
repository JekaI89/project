<?php

namespace App\Domain\Store;

use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Collection;

/**
 * Warehouse / stock read helpers.
 * Prefer calling known SP once names are confirmed from Delphi dmGet.
 */
class StoreRepository
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * Placeholder list — wire to goods_nal_sp_get_list or equivalent when schema is confirmed.
     */
    public function stockPreview(int $limit = 50): Collection
    {
        foreach (['goods_nal', 'v_goods_nal', 'store_nal'] as $table) {
            if (! $this->query->tableExists($table)) {
                continue;
            }
            try {
                return $this->query->select("SELECT * FROM {$table} LIMIT ?", [$limit]);
            } catch (\Throwable) {
                continue;
            }
        }

        return collect();
    }

    /**
     * Example SP call skeleton (mutates=false for read SP).
     */
    public function callStockList(array $params = []): Collection|bool
    {
        // Adjust procedure name to match live DB after inspection.
        // return $this->query->call('goods_nal_sp_get_list', $params, mutates: false);
        return collect();
    }
}

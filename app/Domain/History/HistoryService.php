<?php

namespace App\Domain\History;

use App\Infrastructure\ProductDb\ProductProcedures as P;
use App\Infrastructure\ProductDb\ProductQuery;
use Throwable;

/** history_sp_add — dmGet.history_add */
class HistoryService
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    public function add(int $objId, int $types, string $name, int $userId): bool
    {
        try {
            $this->query->call(P::HISTORY_ADD, [$objId, $types, $name, $userId], mutates: true);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

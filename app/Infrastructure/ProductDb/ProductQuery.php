<?php

namespace App\Infrastructure\ProductDb;

use Illuminate\Support\Collection;

/**
 * Safe query helpers against external product DB.
 * Prefer named methods in Domain services over raw calls from controllers.
 */
class ProductQuery
{
    public function __construct(
        private readonly ProductConnection $connection,
    ) {}

    public function select(string $sql, array $bindings = []): Collection
    {
        return collect($this->connection->db()->select($sql, $bindings));
    }

    public function selectOne(string $sql, array $bindings = []): ?object
    {
        return $this->select($sql, $bindings)->first();
    }

    /**
     * CALL stored procedure. Write-side requires PRODUCT_DB_ALLOW_WRITE=true.
     *
     * @return Collection|bool  select-like result or true for non-select SP
     */
    public function call(string $procedure, array $bindings = [], bool $mutates = true): Collection|bool
    {
        if ($mutates) {
            $this->connection->assertWritable();
        }

        $placeholders = implode(', ', array_fill(0, count($bindings), '?'));
        $sql = sprintf('CALL %s(%s)', $procedure, $placeholders);

        try {
            $rows = $this->connection->db()->select($sql, array_values($bindings));

            return collect($rows);
        } catch (\Throwable $e) {
            // Some MySQL SP return no result set
            if (str_contains($e->getMessage(), 'General error: 2014')
                || str_contains($e->getMessage(), 'no results to fetch')) {
                return true;
            }
            throw $e;
        }
    }

    public function tableExists(string $table): bool
    {
        try {
            $db = $this->connection->db()->getDatabaseName();
            $row = $this->selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$db, $table]
            );

            return (int) ($row->c ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}

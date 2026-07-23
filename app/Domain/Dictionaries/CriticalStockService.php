<?php

namespace App\Domain\Dictionaries;

use App\Domain\Support\DelphiMath;
use App\Domain\Support\RowMapper;
use App\Infrastructure\ProductDb\ProductProcedures as P;
use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Port of Critical_Stock.pas
 *
 * Load: CALL goods_critical_name_sp_load
 * Save: CALL goods_critical_name_sp_save (INOUT cri_id, name) — emulated via UPDATE/INSERT
 * Toggle active: UPDATE goods_critical_name
 * Set default: UPDATE config SET int_value WHERE name='critical_stock'
 * Copy: CALL goods_critical_name_sp_copy(from, to)
 */
class CriticalStockService
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * @return array{items:list<array>, default_id:int, default_name:string, error:?string}
     */
    public function list(): array
    {
        try {
            $rows = $this->query->call(P::CRITICAL_NAME_LOAD, [], mutates: false);
            $items = [];
            if ($rows instanceof Collection) {
                $items = $rows->map(fn ($r) => [
                    'cri_id' => RowMapper::int($r, 'cri_id'),
                    'name' => RowMapper::str($r, 'name'),
                    'active' => RowMapper::bool($r, 'active'),
                ])->values()->all();
            }

            $defId = 0;
            $defName = '';
            try {
                $cfg = $this->query->selectOne(
                    "SELECT int_value FROM config WHERE `name` = 'critical_stock' LIMIT 1"
                );
                $defId = RowMapper::int($cfg ?? [], 'int_value');
                foreach ($items as $it) {
                    if ($it['cri_id'] === $defId) {
                        $defName = $it['name'];
                        break;
                    }
                }
            } catch (Throwable) {
                // ignore
            }

            return [
                'items' => $items,
                'default_id' => $defId,
                'default_name' => $defName,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'items' => [],
                'default_id' => 0,
                'default_name' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok:bool,cri_id:int,message:?string}
     */
    public function save(int $criId, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['ok' => false, 'cri_id' => 0, 'message' => 'Пустое имя'];
        }

        try {
            app(\App\Infrastructure\ProductDb\ProductConnection::class)->assertWritable();
            $db = app(\App\Infrastructure\ProductDb\ProductConnection::class)->db();

            if ($criId > 0) {
                $db->update(
                    'UPDATE goods_critical_name SET `name` = ? WHERE cri_id = ?',
                    [$name, $criId]
                );

                return ['ok' => true, 'cri_id' => $criId, 'message' => null];
            }

            $db->insert(
                'INSERT INTO goods_critical_name (`name`, `active`) VALUES (?, 1)',
                [$name]
            );
            $newId = (int) $db->getPdo()->lastInsertId();

            return ['ok' => true, 'cri_id' => $newId, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'cri_id' => 0, 'message' => $e->getMessage()];
        }
    }

    public function setActive(int $criId, bool $active): array
    {
        try {
            app(\App\Infrastructure\ProductDb\ProductConnection::class)->assertWritable();
            $db = app(\App\Infrastructure\ProductDb\ProductConnection::class)->db();
            $db->update(
                'UPDATE goods_critical_name SET `active` = ? WHERE cri_id = ?',
                [DelphiMath::boolInt($active), $criId]
            );

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function setDefault(int $criId): array
    {
        try {
            app(\App\Infrastructure\ProductDb\ProductConnection::class)->assertWritable();
            $db = app(\App\Infrastructure\ProductDb\ProductConnection::class)->db();
            $db->update(
                "UPDATE config SET int_value = ? WHERE `name` = 'critical_stock'",
                [$criId]
            );

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function copy(int $fromId, int $toId): array
    {
        try {
            $this->query->call(P::CRITICAL_NAME_COPY, [$fromId, $toId], mutates: true);

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}

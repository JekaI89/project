<?php

namespace App\Domain\Finance;

use App\Domain\Support\DelphiMath;
use App\Domain\Support\RowMapper;
use App\Infrastructure\ProductDb\ProductProcedures as P;
use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Port of Finance.pas
 *
 * List:  CALL finance_sp_get_list(in_place_id, in_rab, in_old, in_db, in_de)
 *   in_rab=1 → status=0 (в работе)
 *   in_old=1 → status=1 + date range (проведённые / архив)
 * Save:  finance_sp_save(id, place, type_id, dates, suma, user, kom)
 *   type_id=1 приход, type_id=-1 расход
 * Close: finance_sp_set_close(id, status, user)
 * Del:   finance_sp_del(id)
 */
class FinanceService
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * @return array{items:list<array>, totals:array, balance_all:array, error:?string}
     */
    public function list(
        int $placeId,
        bool $inWork = true,
        bool $archive = false,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        if (! $inWork && ! $archive) {
            $inWork = true;
        }

        $db = $dateFrom ?? date('Y-m-d', strtotime('-31 days'));
        $de = $dateTo ?? date('Y-m-d');

        try {
            $rows = $this->query->call(
                P::FINANCE_LIST,
                [
                    $placeId,
                    DelphiMath::boolInt($inWork),
                    DelphiMath::boolInt($archive),
                    $db,
                    $de,
                ],
                mutates: false
            );

            $items = [];
            $coming = 0.0;
            $expense = 0.0;

            if ($rows instanceof Collection) {
                foreach ($rows as $r) {
                    $mapped = $this->mapRow($r);
                    $items[] = $mapped;
                    if ($mapped['status']) {
                        $coming = DelphiMath::rnd($coming + $mapped['coming'], -2);
                        $expense = DelphiMath::rnd($expense + $mapped['expense'], -2);
                    }
                }
            }

            $balanceAll = $this->balanceAll($placeId);

            return [
                'items' => $items,
                'totals' => [
                    'coming' => $coming,
                    'expense' => $expense,
                    'balance' => DelphiMath::rnd($coming - $expense, -2),
                ],
                'balance_all' => $balanceAll,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'items' => [],
                'totals' => ['coming' => 0, 'expense' => 0, 'balance' => 0],
                'balance_all' => ['coming' => 0, 'expense' => 0, 'balance' => 0],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Balance of all conducted (status=1) for place — Delphi panel caption second line.
     *
     * @return array{coming:float,expense:float,balance:float}
     */
    public function balanceAll(int $placeId): array
    {
        try {
            $r = $this->query->selectOne(
                'SELECT
                    ROUND(SUM(CASE WHEN type_id = 1 THEN suma ELSE 0 END), 2) AS coming,
                    ROUND(SUM(CASE WHEN type_id = -1 THEN suma ELSE 0 END), 2) AS expense
                 FROM finance
                 WHERE place_id = ? AND `status` = 1',
                [$placeId]
            );
            $c = RowMapper::float($r ?? [], 'coming', -2);
            $e = RowMapper::float($r ?? [], 'expense', -2);

            return [
                'coming' => $c,
                'expense' => $e,
                'balance' => DelphiMath::rnd($c - $e, -2),
            ];
        } catch (Throwable) {
            return ['coming' => 0.0, 'expense' => 0.0, 'balance' => 0.0];
        }
    }

    /**
     * @param  array{id:int,place_id:int,dates:string,coming:float,expense:float,kom:string,user_id:int}  $data
     * @return array{ok:bool,id:int,message:?string}
     */
    public function save(array $data): array
    {
        $coming = (float) ($data['coming'] ?? 0);
        $expense = (float) ($data['expense'] ?? 0);

        if ($expense > 0) {
            $typeId = -1;
            $suma = $expense;
        } else {
            $typeId = 1;
            $suma = $coming;
        }

        if ($suma <= 0) {
            return ['ok' => false, 'id' => 0, 'message' => 'Укажите сумму прихода или расхода'];
        }

        $id = (int) ($data['id'] ?? -1);
        if ($id === 0) {
            $id = -1;
        }

        try {
            $rows = $this->query->call(P::FINANCE_SAVE, [
                $id,
                (int) ($data['place_id'] ?? 0),
                $typeId,
                $data['dates'] ?? date('Y-m-d'),
                $suma,
                (int) ($data['user_id'] ?? 0),
                (string) ($data['kom'] ?? ''),
            ], mutates: true);

            $newId = $id;
            if ($rows instanceof Collection && $rows->isNotEmpty()) {
                $newId = RowMapper::int($rows->first(), 'id') ?: $id;
            }

            return ['ok' => true, 'id' => $newId > 0 ? $newId : $id, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'id' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:?string,dates:?string,user_name:?string,status:?bool}
     */
    public function setClose(int $id, bool $conducted, int $userId): array
    {
        try {
            $rows = $this->query->call(
                P::FINANCE_SET_CLOSE,
                [$id, $conducted ? 1 : 0, $userId],
                mutates: true
            );

            $dates = null;
            $userName = null;
            $status = $conducted;
            if ($rows instanceof Collection && $rows->isNotEmpty()) {
                $r = $rows->first();
                $dates = $this->formatDate(RowMapper::get($r, 'dates'));
                $userName = RowMapper::str($r, 'user_name');
                $status = RowMapper::bool($r, 'status');
            }

            return [
                'ok' => true,
                'message' => null,
                'dates' => $dates,
                'user_name' => $userName,
                'status' => $status,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'dates' => null,
                'user_name' => null,
                'status' => null,
            ];
        }
    }

    /**
     * @return array{ok:bool,message:?string}
     */
    public function delete(int $id): array
    {
        try {
            $rows = $this->query->call(P::FINANCE_DEL, [$id], mutates: true);
            $error = '';
            if ($rows instanceof Collection && $rows->isNotEmpty()) {
                $error = RowMapper::str($rows->first(), 'error');
            }
            if ($error !== '') {
                return ['ok' => false, 'message' => $error];
            }

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function mapRow(object|array $r): array
    {
        $coming = RowMapper::float($r, 'coming', -2);
        $expense = RowMapper::float($r, 'expense', -2);

        return [
            'id' => RowMapper::int($r, 'id'),
            'dates' => $this->formatDate(RowMapper::get($r, 'dates')),
            'dates_raw' => $this->dateYmd(RowMapper::get($r, 'dates')),
            'coming' => $coming,
            'expense' => $expense,
            'summa' => DelphiMath::rnd($coming - $expense, -2),
            'kom' => RowMapper::str($r, 'kom'),
            'status' => RowMapper::bool($r, 'status'),
            'user_name' => RowMapper::str($r, 'user_name'),
        ];
    }

    private function formatDate(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('d.m.Y');
        }
        $ts = strtotime((string) $v);

        return $ts ? date('d.m.Y', $ts) : (string) $v;
    }

    private function dateYmd(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        $ts = strtotime((string) $v);

        return $ts ? date('Y-m-d', $ts) : (string) $v;
    }
}

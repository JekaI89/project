<?php

namespace App\Domain\Orders;

use App\Domain\Support\DelphiMath;
use App\Domain\Support\RowMapper;
use App\Infrastructure\ProductDb\ProductProcedures as P;
use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Port of Orders_Clients.pas list + set_status.
 *
 * SP: orders_clients_sp_get_list(in_rab, in_old, in_db, in_de)
 * SP: orders_clients_sp_set_status(in_ord_id, in_status, in_user_id, in_podr_id, in_param)
 *
 * Production flags (dfm multi-titles):
 *  Медынь:   st1 резерв, st2 принят, st3 произведён, tk1 ТК
 *  Пирогово: st6 резерв, st4 принят, st5 произведён, tk2 ТК
 *  st10 — поступил на склад / реально отгружен
 */
class OrdersService
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * @return array{items:list<array>, error:?string}
     */
    public function list(bool $inWork = true, bool $archive = false, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Delphi create_form: de=today, db=today-31
        $db = $dateFrom ?? date('Y-m-d', strtotime('-31 days'));
        $de = $dateTo ?? date('Y-m-d');

        try {
            $rows = $this->query->call(
                P::ORDERS_LIST,
                [
                    DelphiMath::boolInt($inWork),
                    DelphiMath::boolInt($archive),
                    $db,
                    $de,
                ],
                mutates: false
            );

            if (! $rows instanceof Collection) {
                return ['items' => [], 'error' => null];
            }

            $items = $rows->map(fn ($r) => $this->mapOrderRow($r))->values()->all();

            return ['items' => $items, 'error' => null];
        } catch (Throwable $e) {
            return ['items' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Toggle production status flag (st1..st6, st10).
     * in_param: 1 = set on, 0 = set off (Delphi sets() when unchecking).
     */
    public function setStatus(int $ordId, int $status, int $userId, int $podrId = 0, int $param = 0): array
    {
        try {
            $rows = $this->query->call(
                P::ORDERS_SET_STATUS,
                [$ordId, $status, $userId, $podrId, $param],
                mutates: true
            );

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

    private function mapOrderRow(object|array $r): array
    {
        $colorRaw = RowMapper::str($r, 'color');

        return [
            'ord_id' => RowMapper::int($r, 'ord_id'),
            'st0' => $this->formatDate(RowMapper::get($r, 'st0'), false),
            'st0_raw' => (string) (RowMapper::get($r, 'st0') ?? ''),
            'ord_num' => RowMapper::str($r, 'ord_num'),
            'indexs' => RowMapper::str($r, 'indexs') !== ''
                ? RowMapper::str($r, 'indexs')
                : RowMapper::str($r, 'index'),
            'clients' => RowMapper::str($r, 'clients'),
            'payment_num' => RowMapper::str($r, 'payment'),
            'payment' => RowMapper::str($r, 'payment'),
            'series' => RowMapper::str($r, 'series'),
            'model' => RowMapper::str($r, 'model'),
            'quant' => RowMapper::int($r, 'quant'),
            'types' => RowMapper::str($r, 'types'),
            'ready' => $this->formatDate(RowMapper::get($r, 'ready'), false),
            'ready_raw' => (string) (RowMapper::get($r, 'ready') ?? ''),
            'comments' => RowMapper::str($r, 'comments'),
            // SP field is conditions (plural)
            'conditions' => RowMapper::str($r, 'conditions') !== ''
                ? RowMapper::str($r, 'conditions')
                : RowMapper::str($r, 'condition'),
            'st1' => RowMapper::bool($r, 'st1'),
            'st2' => RowMapper::bool($r, 'st2'),
            'st3' => RowMapper::bool($r, 'st3'),
            'st4' => RowMapper::bool($r, 'st4'),
            'st5' => RowMapper::bool($r, 'st5'),
            'st6' => RowMapper::bool($r, 'st6'),
            'st10' => RowMapper::bool($r, 'st10'),
            'st10d' => $this->formatDate(RowMapper::get($r, 'st10d'), false),
            'user_id' => RowMapper::int($r, 'user_id'),
            'podr_id' => RowMapper::int($r, 'podr_id'),
            'tk1' => RowMapper::str($r, 'tk1'),
            'tk2' => RowMapper::str($r, 'tk2'),
            'st1d' => $this->formatDate(RowMapper::get($r, 'st1d'), false),
            'st2d' => $this->formatDate(RowMapper::get($r, 'st2d'), false),
            'st3d' => $this->formatDate(RowMapper::get($r, 'st3d'), false),
            'st4d' => $this->formatDate(RowMapper::get($r, 'st4d'), false),
            'st5d' => $this->formatDate(RowMapper::get($r, 'st5d'), false),
            'st6d' => $this->formatDate(RowMapper::get($r, 'st6d'), false),
            'out_routing' => RowMapper::int($r, 'out_routing'),
            'err' => RowMapper::bool($r, 'err'),
            'err_tk' => RowMapper::bool($r, 'err_tk'),
            'color' => $this->cssColor($colorRaw),
            'color_raw' => $colorRaw,
            'vsklad' => RowMapper::bool($r, 'vsklad'),
            'lock_user' => RowMapper::int($r, 'lock_user'),
            'lock_name' => RowMapper::str($r, 'lock_name'),
        ];
    }

    private function formatDate(mixed $value, bool $withTime): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
        }
        $ts = strtotime((string) $value);

        return $ts ? date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $ts) : (string) $value;
    }

    /**
     * Delphi color may be clXxx, $BBGGRR integer string, or #hex.
     */
    private function cssColor(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '0'
            || strcasecmp($raw, 'clWindow') === 0
            || strcasecmp($raw, 'clNone') === 0
            || strcasecmp($raw, 'clWhite') === 0
            || strcasecmp($raw, 'clBtnFace') === 0) {
            return '';
        }
        if ($raw[0] === '#') {
            return $raw;
        }
        $named = [
            'clYellow' => '#ffff99',
            'clAqua' => '#ccffff',
            'clLime' => '#ccffcc',
            'clSilver' => '#e0e0e0',
            'clGray' => '#c0c0c0',
            'clRed' => '#ffcccc',
            'clSkyBlue' => '#cceeff',
            'clMoneyGreen' => '#c0dcc0',
            'clCream' => '#fffbf0',
            'clInfoBk' => '#ffffe1',
        ];
        if (isset($named[$raw])) {
            return $named[$raw];
        }
        // $00BBGGRR Delphi integer
        if (preg_match('/^\$?([0-9A-Fa-f]{6,8})$/', ltrim($raw, '$'), $m)) {
            $hex = str_pad($m[1], 8, '0', STR_PAD_LEFT);
            $bb = substr($hex, -6, 2);
            $gg = substr($hex, -4, 2);
            $rr = substr($hex, -2, 2);

            return '#'.$rr.$gg.$bb;
        }
        if (is_numeric($raw)) {
            $n = (int) $raw;
            $rr = $n & 0xFF;
            $gg = ($n >> 8) & 0xFF;
            $bb = ($n >> 16) & 0xFF;

            return sprintf('#%02x%02x%02x', $rr, $gg, $bb);
        }

        return '';
    }
}

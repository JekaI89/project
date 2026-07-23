<?php

namespace App\Domain\Dictionaries;

use App\Domain\Support\RowMapper;
use App\Infrastructure\ProductDb\ProductConnection;
use App\Infrastructure\ProductDb\ProductQuery;
use Throwable;

/**
 * Port of Item_Status.pas
 * SELECT status, name, color, index FROM orders_clients_status_name ORDER BY num, index
 * UPDATE color (pravo 44)
 */
class OrderStatusService
{
    public function __construct(
        private readonly ProductQuery $query,
        private readonly ProductConnection $connection,
    ) {}

    /**
     * @return array{items:list<array>, error:?string}
     */
    public function list(): array
    {
        try {
            $rows = $this->query->select(
                'SELECT `status`, `name`, color, `index`, num
                 FROM orders_clients_status_name
                 ORDER BY num, `index`'
            );
            $items = $rows->map(fn ($r) => [
                'status' => RowMapper::int($r, 'status'),
                'name' => RowMapper::str($r, 'name'),
                'color' => RowMapper::str($r, 'color'),
                'css' => $this->cssColor(RowMapper::str($r, 'color')),
                'index' => RowMapper::str($r, 'index'),
                'num' => RowMapper::int($r, 'num'),
            ])->values()->all();

            return ['items' => $items, 'error' => null];
        } catch (Throwable $e) {
            return ['items' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:?string}
     */
    public function setColor(int $status, string $index, string $color): array
    {
        try {
            $this->connection->assertWritable();
            $this->connection->db()->update(
                'UPDATE orders_clients_status_name SET color = ? WHERE `status` = ? AND `index` = ?',
                [$color, $status, $index]
            );

            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function cssColor(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || strcasecmp($raw, 'clWhite') === 0 || strcasecmp($raw, 'clWindow') === 0) {
            return '';
        }
        $named = [
            'clYellow' => '#ffff99',
            'clSilver' => '#c0c0c0',
            'clRed' => '#ffcccc',
            'clLime' => '#ccffcc',
            'clAqua' => '#ccffff',
            'clSkyBlue' => '#87ceeb',
            'clGray' => '#a0a0a0',
            'clMoneyGreen' => '#c0dcc0',
            'clCream' => '#fffbf0',
        ];
        if (isset($named[$raw])) {
            return $named[$raw];
        }
        if (preg_match('/^\$([0-9A-Fa-f]{6,8})$/', $raw, $m)) {
            $hex = str_pad($m[1], 8, '0', STR_PAD_LEFT);
            $bb = substr($hex, -6, 2);
            $gg = substr($hex, -4, 2);
            $rr = substr($hex, -2, 2);

            return '#'.$rr.$gg.$bb;
        }
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $raw)) {
            return $raw;
        }

        return '';
    }
}

<?php

namespace App\Infrastructure\ProductDb;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Guarded access to the external production MySQL.
 * Do not use DB::connection('product') outside this package.
 */
class ProductConnection
{
    public function db(): ConnectionInterface
    {
        return DB::connection('product');
    }

    public function allowWrite(): bool
    {
        return (bool) config('product.allow_write', false);
    }

    public function assertWritable(): void
    {
        if (! $this->allowWrite()) {
            throw new RuntimeException(
                'Запись во внешнюю БД product запрещена (PRODUCT_DB_ALLOW_WRITE=false).'
            );
        }
    }

    public function ping(): array
    {
        $cfg = config('database.connections.product', []);
        $host = (string) ($cfg['host'] ?? '');
        $user = (string) ($cfg['username'] ?? '');
        $port = (int) ($cfg['port'] ?? 3306);

        if ($host === '' || $user === '') {
            return [
                'ok' => false,
                'message' => 'Заполните PRODUCT_DB_HOST и PRODUCT_DB_USERNAME в .env',
            ];
        }

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 2.0);
        if ($fp === false) {
            return [
                'ok' => false,
                'message' => "host {$host}:{$port} недоступен ({$errstr})",
            ];
        }
        fclose($fp);

        try {
            $prev = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', '3');
            $this->db()->select('SELECT 1 AS ok');
            ini_set('default_socket_timeout', (string) $prev);

            return ['ok' => true, 'message' => "connected {$host}:{$port}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}

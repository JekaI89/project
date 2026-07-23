<?php

namespace App\Domain\System;

use App\Infrastructure\Files\ProductFileCatalog;
use App\Infrastructure\ProductDb\ProductConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthService
{
    public function __construct(
        private readonly ProductConnection $product,
        private readonly ProductFileCatalog $files,
    ) {}

    public function status(): array
    {
        return [
            'app_db' => $this->checkAppDb(),
            'product_db' => $this->product->ping(),
            'product_write' => [
                'ok' => true,
                'allowed' => $this->product->allowWrite(),
                'message' => $this->product->allowWrite()
                    ? 'PRODUCT_DB_ALLOW_WRITE=true'
                    : 'write disabled (read/SP pilot mode)',
            ],
            'redis' => $this->checkRedis(),
            'files' => $this->files->ping(),
        ];
    }

    private function checkAppDb(): array
    {
        try {
            DB::connection()->select('SELECT 1');

            return ['ok' => true, 'message' => 'connected'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();

            return ['ok' => true, 'message' => 'pong'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}

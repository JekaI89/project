<?php

namespace App\Providers;

use App\Domain\Auth\ProductUserAuth;
use App\Domain\Goods\GoodsService;
use App\Domain\History\HistoryService;
use App\Domain\Orders\OrdersService;
use App\Domain\Podr\PodrRepository;
use App\Domain\Purchase\PurchaseService;
use App\Domain\Routing\RoutingService;
use App\Domain\Store\StoreService;
use App\Infrastructure\Files\CatalogPath;
use App\Infrastructure\ProductDb\ProductConnection;
use App\Infrastructure\ProductDb\ProductQuery;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProductConnection::class);
        $this->app->singleton(ProductQuery::class);
        $this->app->singleton(CatalogPath::class);
        $this->app->singleton(PodrRepository::class);
        $this->app->singleton(GoodsService::class);
        $this->app->singleton(StoreService::class);
        $this->app->singleton(OrdersService::class);
        $this->app->singleton(PurchaseService::class);
        $this->app->singleton(RoutingService::class);
        $this->app->singleton(HistoryService::class);
        $this->app->singleton(ProductUserAuth::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}

<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\CatalogImageController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MovesController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\Dictionaries\CriticalStockController;
use App\Http\Controllers\Dictionaries\OrderStatusController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Delphi Img_Low / Image_Show — goods|inv|kit thumbnails
    Route::get('/catalog/img/{kind}/{id}', [CatalogImageController::class, 'show'])
        ->whereIn('kind', ['goods', 'inv', 'inventory', 'kit'])
        ->whereNumber('id')
        ->name('catalog.img');

    Route::get('/goods', [GoodsController::class, 'index'])->name('goods.index');
    Route::get('/goods/{id}', [GoodsController::class, 'show'])->name('goods.show')->whereNumber('id');
    Route::put('/goods/{id}', [GoodsController::class, 'update'])->name('goods.update')->whereNumber('id');

    Route::get('/store', [StoreController::class, 'index'])->name('store.index');
    Route::post('/store/move-line', [StoreController::class, 'moveLine'])->name('store.move-line');

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::post('/orders/status', [OrdersController::class, 'setStatus'])->name('orders.status');

    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase.index');
    Route::get('/purchase/{id}', [PurchaseController::class, 'show'])->name('purchase.show')->whereNumber('id');
    Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->name('purchase.update')->whereNumber('id');
    Route::post('/purchase/status', [PurchaseController::class, 'setStatus'])->name('purchase.status');
    Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');
    Route::get('/routing/{id}', [RoutingController::class, 'show'])->name('routing.show')->whereNumber('id');
    Route::post('/routing/activ', [RoutingController::class, 'setActiv'])->name('routing.activ');
    Route::get('/moves', [MovesController::class, 'index'])->name('moves.index');
    Route::get('/moves/{id}', [MovesController::class, 'show'])->name('moves.show')->whereNumber('id');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/status', [InventoryController::class, 'setStatus'])->name('inventory.status');
    // Finance.pas
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');
    Route::post('/finance/status', [FinanceController::class, 'setStatus'])->name('finance.status');
    Route::post('/finance/delete', [FinanceController::class, 'destroy'])->name('finance.delete');

    // Calendars_Plan.pas
    Route::get('/plan', [PlanController::class, 'index'])->name('plan.index');
    Route::post('/plan/save', [PlanController::class, 'save'])->name('plan.save');
    Route::post('/plan/status', [PlanController::class, 'setStatus'])->name('plan.status');

    // Task_Find / Task_Edit / Task_New
    Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TasksController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TasksController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}', [TasksController::class, 'show'])->name('tasks.show')->whereNumber('id');
    Route::post('/tasks/status', [TasksController::class, 'setStatus'])->name('tasks.status');
    Route::post('/tasks/note', [TasksController::class, 'addNote'])->name('tasks.note');

    // Delphi TMainMenu → Отчёты
    Route::get('/reports', fn () => app(ModulePlaceholderController::class)('reports'))->name('reports.index');
    Route::get('/reports/{slug}', [ReportsController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('reports.show');

    // Справочники
    Route::get('/dictionaries/critical-stock', [CriticalStockController::class, 'index'])->name('dictionaries.critical');
    Route::post('/dictionaries/critical-stock', [CriticalStockController::class, 'save'])->name('dictionaries.critical.save');
    Route::post('/dictionaries/critical-stock/active', [CriticalStockController::class, 'setActive'])->name('dictionaries.critical.active');
    Route::post('/dictionaries/critical-stock/default', [CriticalStockController::class, 'setDefault'])->name('dictionaries.critical.default');
    Route::post('/dictionaries/critical-stock/copy', [CriticalStockController::class, 'copy'])->name('dictionaries.critical.copy');

    Route::get('/dictionaries/order-statuses', [OrderStatusController::class, 'index'])->name('dictionaries.order-statuses');
    Route::post('/dictionaries/order-statuses/color', [OrderStatusController::class, 'setColor'])->name('dictionaries.order-statuses.color');

    Route::get('/dictionaries/{slug}', function (string $slug) {
        return app(ModulePlaceholderController::class)('dictionaries', $slug);
    })->where('slug', '[a-z0-9\-]+')->name('dictionaries.show');

    // Delphi Инструменты
    Route::get('/tools/replace-in-tc', [ToolsController::class, 'replaceForm'])->name('tools.replace');
    Route::post('/tools/replace-in-tc', [ToolsController::class, 'replaceRun'])->name('tools.replace.run');
    Route::get('/tools/copy-test-data', [ToolsController::class, 'copyForm'])->name('tools.copy');
    Route::post('/tools/copy-test-data', [ToolsController::class, 'copyRun'])->name('tools.copy.run');

    Route::get('/tools/{slug}', function (string $slug) {
        return app(ModulePlaceholderController::class)('tools', $slug);
    })->where('slug', '[a-z0-9\-]+')->name('tools.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/config', [SettingsController::class, 'saveConfig'])->name('settings.config');
    Route::post('/settings/users', [SettingsController::class, 'saveUser'])->name('settings.users.save');
    Route::post('/settings/users/deactivate', [SettingsController::class, 'deactivateUser'])->name('settings.users.deactivate');
    Route::post('/settings/users/copy', [SettingsController::class, 'copyUser'])->name('settings.users.copy');
    Route::post('/settings/pravo', [SettingsController::class, 'setPravo'])->name('settings.pravo');
});

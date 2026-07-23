<?php

namespace App\Http\Controllers;

use App\Domain\Orders\OrdersService;
use App\Infrastructure\ProductDb\ProductConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrdersController extends Controller
{
    public function index(Request $request, OrdersService $orders, ProductConnection $product): Response
    {
        $ping = $product->ping();
        $user = $request->user();

        // Delphi: «Заказы в работе» / «Закрытые заказы» + dates db..de
        $inWork = $request->boolean('rab', true);
        $archive = $request->boolean('old', false);
        $db = (string) $request->input('db', now()->subDays(31)->toDateString());
        $de = (string) $request->input('de', now()->toDateString());
        $q = trim((string) $request->input('q', ''));

        $canEdit = $user?->is_admin
            || ($user?->hasPravo(1, -1) ?? false)
            || ($user?->hasPravo(7, -1) ?? false);

        $writeAllowed = $product->allowWrite();

        /*
         * Heavy CALL orders_clients_sp_get_list (~30s locally) is deferred once:
         * page shell (filters + layout) arrives immediately; rows load in a second request.
         */
        return Inertia::render('Orders/Index', [
            'filters' => [
                'rab' => $inWork,
                'old' => $archive,
                'db' => $db,
                'de' => $de,
                'q' => $q,
            ],
            // Single deferred prop → single SP call (do not split into items/error/count)
            'list' => Inertia::defer(function () use ($orders, $ping, $inWork, $archive, $db, $de, $q) {
                if (! ($ping['ok'] ?? false)) {
                    return [
                        'items' => [],
                        'error' => $ping['message'] ?? 'Product DB недоступна',
                        'count' => 0,
                    ];
                }

                $result = $orders->list($inWork, $archive, $db, $de);
                $items = $result['items'];

                if ($q !== '') {
                    $ql = mb_strtolower($q);
                    $items = array_values(array_filter($items, function (array $row) use ($ql) {
                        return str_contains(mb_strtolower((string) $row['ord_num']), $ql)
                            || str_contains(mb_strtolower((string) $row['clients']), $ql)
                            || str_contains(mb_strtolower((string) $row['model']), $ql)
                            || str_contains(mb_strtolower((string) $row['comments']), $ql)
                            || str_contains(mb_strtolower((string) $row['payment_num']), $ql)
                            || str_contains(mb_strtolower((string) $row['series']), $ql)
                            || str_contains(mb_strtolower((string) $row['indexs']), $ql)
                            || str_contains((string) $row['ord_id'], $ql)
                            || str_contains(mb_strtolower((string) $row['conditions']), $ql);
                    }));
                }

                return [
                    'items' => $items,
                    'error' => $result['error'],
                    'count' => count($items),
                ];
            }),
            'meta' => [
                'product_db' => $ping,
                'write_allowed' => $writeAllowed,
                'can_edit' => $canEdit,
            ],
        ]);
    }

    public function setStatus(Request $request, OrdersService $orders): RedirectResponse
    {
        $data = $request->validate([
            'ord_id' => ['required', 'integer'],
            'status' => ['required', 'integer'],
            'podr_id' => ['nullable', 'integer'],
            'param' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $res = $orders->setStatus(
            (int) $data['ord_id'],
            (int) $data['status'],
            (int) ($user->product_user_id ?: $user->id),
            (int) ($data['podr_id'] ?? 0),
            (int) ($data['param'] ?? 0),
        );

        if (! $res['ok']) {
            return back()->with('error', $res['message'] ?? 'Ошибка статуса');
        }

        return back()->with('success', 'Статус обновлён');
    }
}

<?php

/**
 * Product ERP integration settings.
 * Navigation mirrors Delphi Main.dfm:
 *  - nav        = PageControl1 (основные вкладки)
 *  - warehouses = PageControl2 (вкладки внутри «Склады»)
 *  - top_menu   = TMainMenu mm (Справочники / Инструменты / Отчёты)
 */
return [
    'allow_write' => (bool) env('PRODUCT_DB_ALLOW_WRITE', false),

    'catalogs' => [
        'img' => env('CATALOG_IMG', 'Img'),
        'file' => env('CATALOG_FILE', 'Files'),
        'zakaz' => env('CATALOG_ZAKAZ', 'Zakaz'),
        'finance' => env('CATALOG_FINANCE', 'Finance'),
        'routing' => env('CATALOG_ROUTING', 'Routing'),
    ],

    /**
     * Absolute path to Delphi Dir_Catalog_Img root (Product.ini files_product).
     */
    'img_path' => env('PRODUCT_IMG_PATH', ''),

    'pravo' => [
        'admin' => 1,
        'users' => 44,
        'setup' => 100,
        'tasks' => 93,
        'routing_files_read_sklad' => 89,
        'routing_files_add_sklad' => 87,
        'routing_files_del_sklad' => 88,
        'routing_files_read_proizv' => 92,
        'routing_files_add_proizv' => 90,
        'routing_files_del_proizv' => 91,
    ],

    /*
     | PageControl1 — основные модули (левое меню web = вкладки Delphi)
     | Порядок как в Main.dfm: Заказы → ТК → Склады → Закупки → Финансы → План → Задачи
     */
    'nav' => [
        [
            'key' => 'orders',
            'label' => 'Текущие заказы',
            'href' => '/orders',
            'icon' => 'bi-cart',
            'match' => ['/orders'],
            'delphi' => 'Orders_Clients',
        ],
        [
            'key' => 'routing',
            'label' => 'Технологические карты',
            'href' => '/routing',
            'icon' => 'bi-diagram-3',
            'match' => ['/routing'],
            'delphi' => 'listTC, TC',
        ],
        [
            'key' => 'warehouses',
            'label' => 'Склады',
            'href' => '/store?mode=parts',
            'icon' => 'bi-building',
            'match' => ['/store', '/moves', '/inventory'],
            'delphi' => 'PageControl2',
            // PageControl2 TabPosition=tpLeft — порядок Main.dfm
            'children' => [
                [
                    'key' => 'parts',
                    'label' => 'Комплектующие',
                    'href' => '/store?mode=parts',
                    'match' => ['mode=parts'],
                    'delphi' => 'Store groups=1',
                ],
                [
                    'key' => 'production',
                    'label' => 'Производство',
                    'href' => '/store?mode=production',
                    'match' => ['mode=production'],
                    'delphi' => 'Store1 groups=2',
                ],
                [
                    'key' => 'finished',
                    'label' => 'Готовая продукция',
                    'href' => '/store?mode=finished',
                    'match' => ['mode=finished'],
                    'delphi' => 'Store2 groups=3',
                ],
                [
                    'key' => 'transit',
                    'label' => 'В пути',
                    'href' => '/store?mode=transit',
                    'match' => ['mode=transit'],
                    'delphi' => 'Store2 groups=4',
                ],
                [
                    'key' => 'moves',
                    'label' => 'Перемещения',
                    'href' => '/moves',
                    'match' => ['/moves'],
                    'delphi' => 'Nakl',
                ],
                [
                    'key' => 'inventory',
                    'label' => 'Инвентаризация',
                    'href' => '/inventory',
                    'match' => ['/inventory'],
                    'delphi' => 'Inv',
                ],
            ],
        ],
        [
            'key' => 'purchase',
            'label' => 'Закупки',
            'href' => '/purchase',
            'icon' => 'bi-bag',
            'match' => ['/purchase'],
            'delphi' => 'Zakup',
        ],
        [
            'key' => 'finance',
            'label' => 'Финансы',
            'href' => '/finance',
            'icon' => 'bi-cash-stack',
            'match' => ['/finance'],
            'delphi' => 'Finance',
        ],
        [
            'key' => 'plan',
            'label' => 'План',
            'href' => '/plan',
            'icon' => 'bi-calendar3',
            'match' => ['/plan'],
            'delphi' => 'Calendars_Plan',
        ],
        [
            'key' => 'tasks',
            'label' => 'Задачи',
            'href' => '/tasks',
            'icon' => 'bi-list-task',
            'match' => ['/tasks'],
            'delphi' => 'Task / pTask',
        ],
        // Бывший TMainMenu — в левом меню, подменю как у «Склады»
        [
            'key' => 'dictionaries',
            'label' => 'Справочники',
            'href' => '/goods',
            'icon' => 'bi-journal-bookmark',
            'match' => ['/goods', '/dictionaries', '/settings'],
            'delphi' => 'MainMenu Справочники',
            'children' => [
                [
                    'key' => 'users',
                    'label' => 'Пользователи',
                    'href' => '/settings?tab=users',
                    'match' => ['tab=users'],
                    'pravo' => 1,
                    'delphi' => 'listUSERS',
                ],
                [
                    'key' => 'order_statuses',
                    'label' => 'Статусы текущих заказов',
                    'href' => '/dictionaries/order-statuses',
                    'match' => ['/dictionaries/order-statuses'],
                    'delphi' => 'Item_Status',
                ],
                [
                    'key' => 'critical_stock',
                    'label' => 'Критический уровень запаса',
                    'href' => '/dictionaries/critical-stock',
                    'match' => ['/dictionaries/critical-stock'],
                    'delphi' => 'Critical_Stock',
                ],
                [
                    'key' => 'goods',
                    'label' => 'Номенклатура',
                    'href' => '/goods',
                    'match' => ['/goods'],
                    'delphi' => 'Goods',
                ],
                [
                    'key' => 'sync_images',
                    'label' => 'Синхронизировать изображения',
                    'href' => '/settings?tab=config',
                    'match' => ['tab=config'],
                    'pravo' => 1,
                    'delphi' => 'DirImgAllSync / Setup img',
                ],
                [
                    'key' => 'setup',
                    'label' => 'Настройки',
                    'href' => '/settings?tab=config',
                    'match' => ['/settings'],
                    'pravo' => 1,
                    'delphi' => 'Setup',
                ],
                [
                    'key' => 'version',
                    'label' => 'Версия ПО и БД',
                    'href' => '/settings?tab=system',
                    'match' => ['tab=system'],
                    'delphi' => 'D1Click',
                ],
            ],
        ],
        [
            'key' => 'tools',
            'label' => 'Инструменты',
            'href' => '/tools/replace-in-tc',
            'icon' => 'bi-tools',
            'match' => ['/tools'],
            'delphi' => 'MainMenu Инструменты',
            'children' => [
                [
                    'key' => 'replace_in_tc',
                    'label' => 'Замена товара в ТК',
                    'href' => '/tools/replace-in-tc',
                    'match' => ['/tools/replace-in-tc'],
                    'delphi' => 'N12Click',
                ],
                [
                    'key' => 'copy_test_data',
                    'label' => 'Копирование данных для тестирования',
                    'href' => '/tools/copy-test-data',
                    'match' => ['/tools/copy-test-data'],
                    'delphi' => 'N13Click / x_sys_copy',
                ],
            ],
        ],
        [
            'key' => 'reports',
            'label' => 'Отчёты',
            'href' => '/reports/shipped',
            'icon' => 'bi-bar-chart',
            // Не включаем /moves — иначе активна секция «Склады»
            'match' => ['/reports'],
            'delphi' => 'MainMenu Отчёты',
            'children' => [
                [
                    'key' => 'shipped',
                    'label' => 'Отгружено продукции клиенту',
                    'href' => '/reports/shipped',
                    'match' => ['/reports/shipped'],
                    'delphi' => 'Otchet1',
                ],
                [
                    'key' => 'goods_move',
                    'label' => 'Перемещение товара',
                    'href' => '/reports/goods-move',
                    'match' => ['/reports/goods-move'],
                    'delphi' => 'Otchet2 / x_otchet_move_goods',
                ],
                [
                    'key' => 'duplicate_cards',
                    'label' => 'Задвоенные карточки товара',
                    'href' => '/reports/duplicate-cards',
                    'match' => ['/reports/duplicate-cards'],
                    'delphi' => 'Otchet3',
                ],
                [
                    'key' => 'deleted_orders',
                    'label' => 'Удалённые заказы клиента',
                    'href' => '/reports/deleted-orders',
                    'match' => ['/reports/deleted-orders'],
                    'delphi' => 'Otchet4',
                ],
                [
                    'key' => 'price_finished',
                    'label' => 'Установка прайса готовой продукции',
                    'href' => '/reports/price-finished',
                    'match' => ['/reports/price-finished'],
                    'delphi' => 'Otchet5',
                ],
                [
                    'key' => 'history',
                    'label' => 'Журнал истории',
                    'href' => '/reports/history',
                    'match' => ['/reports/history'],
                    'delphi' => 'Otchet6 / hist',
                ],
                [
                    'key' => 'stock_mismatch',
                    'label' => 'Несоответствие наличия товара',
                    'href' => '/reports/stock-mismatch',
                    'match' => ['/reports/stock-mismatch'],
                    'delphi' => 'Otchet7',
                ],
            ],
        ],
    ],

    /**
     * top_menu больше не используется в UI (всё в левом nav).
     * Оставлено пустым для совместимости HandleInertiaRequests.
     */
    'top_menu' => [],

    // legacy flat modules key kept for compatibility
    'modules' => [],
];

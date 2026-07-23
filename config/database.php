<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        /*
         | Internal app DB — Laravel migrations, users, prefs.
         */
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'product_app'),
            'username' => env('DB_USERNAME', 'product'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        /*
         | External production MySQL — source of truth.
         | Use only via App\Infrastructure\ProductDb\* (SELECT + CALL).
         */
        'product' => [
            'driver' => 'mysql',
            'host' => env('PRODUCT_DB_HOST', '127.0.0.1'),
            'port' => env('PRODUCT_DB_PORT', '3306'),
            'database' => env('PRODUCT_DB_DATABASE', 'product'),
            'username' => env('PRODUCT_DB_USERNAME', 'root'),
            'password' => env('PRODUCT_DB_PASSWORD', ''),
            'charset' => env('PRODUCT_DB_CHARSET', 'utf8mb4'),
            'collation' => env('PRODUCT_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_TIMEOUT => 3,
            ]) : [],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'product'), '_').'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];

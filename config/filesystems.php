<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         | External structured files (Delphi SrvFiles:\files_product\...).
         */
        'product_ftp' => [
            'driver' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD'),
            'port' => (int) env('FTP_PORT', 21),
            'root' => env('FTP_ROOT', '/files_product'),
            'passive' => filter_var(env('FTP_PASSIVE', true), FILTER_VALIDATE_BOOL),
            'ssl' => filter_var(env('FTP_SSL', false), FILTER_VALIDATE_BOOL),
            'timeout' => 30,
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];

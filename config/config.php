<?php

return [
    'wordpress' => [
        'driver' => 'mysql',
        'host' => env('WB_DB_HOST', 'localhost'),
        'port' => env('WB_DB_PORT', '1433'),
        'database' => env('WB_DB_DATABASE', 'YOUR_DATABASE_NAME'),
        'username' => env('WB_DB_USERNAME', 'USERNAME'),
        'password' => env('WB_DB_PASSWORD', ''),
        'prefix' => env('WB_DB_PREFIX', 'wp_'),
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];


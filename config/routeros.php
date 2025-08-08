<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RouterOS Default Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the default connection settings for RouterOS.
    |
    */

    'host' => env('ROUTEROS_HOST', '192.168.1.1'),
    'user' => env('ROUTEROS_USER', 'admin'),
    'pass' => env('ROUTEROS_PASS', 'password'),
    'port' => env('ROUTEROS_PORT', 8728),
    'timeout' => env('ROUTEROS_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | RouterOS Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define multiple RouterOS connections for your application.
    |
    */

    'connections' => [
        'default' => [
            'host' => env('ROUTEROS_HOST', '192.168.1.1'),
            'user' => env('ROUTEROS_USER', 'admin'),
            'pass' => env('ROUTEROS_PASS', 'password'),
            'port' => env('ROUTEROS_PORT', 8728),
            'timeout' => env('ROUTEROS_TIMEOUT', 10),
        ],
    ],
];
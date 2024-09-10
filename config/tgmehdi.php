<?php
return [
    'url_prefix' => env('TGMEHDI_URL_PREFIX', '/tgmehdi'),
    'bots' => [
        'default' => [
            'token' => env('TGMEHDI_TOKEN'),
            'secret_token' => env('TGMEHDI_SECRET_TOKEN'),
            'route' => 'default',            'auth' => [
                'role' => null,
                'shared' => null,
                'UserModel' => null
            ],
            'cache_optimisation' => true,
            'request_queue' => null
        ]
    ],

    'proxy' => env('TGMEHDI_PROXY'),
    'middleware' => '',
    'parse_mode' => 'markdown',
    'chat' => '\\App\\Models\\Chat',
];

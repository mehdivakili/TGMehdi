<?php
return [
    'url_prefix' => env('TGMEHDI_URL_PREFIX', '/tgmehdi'),
    'bots' => [
        'default' => [
            'token' => env('TGMEHDI_TOKEN'),
            'secret_token' => env('TGMEHDI_SECRET_TOKEN'),
            'route' => 'default',
            'debug' => env('TGMEHDI_DEBUG', false),
            'allowed_chats' => ['private'],
            'shared' => null,
            'cache_optimisation' => true,
            'message_queue' => null
        ]
    ],

    'proxy' => env('TGMEHDI_PROXY'),
    'middleware' => '',
    'parse_mode' => 'markdown',
    'chat' => '\\App\\Models\\Chat',
    'self_signed_webhook' => [
        'active' => false,
        'certificate' => env('TGMEHDI_CERTIFICATE', null),
    ]
];

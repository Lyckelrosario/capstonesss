<?php
return [
    'default' => env('MAIL_MAILER', 'smtp'),
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],
        'log' => ['transport' => 'log'],
    ],
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'name' => env('MAIL_FROM_NAME', '4BS Geoff Professional')],
];

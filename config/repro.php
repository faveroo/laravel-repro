<?php

return [
    'enabled' => env('LARAVEL_REPRO_ENABLED', false),

    'disk' => env('LARAVEL_REPRO_DISK', 'local'),

    'path' => env('LARAVEL_REPRO_PATH', 'laravel-repro'),

    'redact' => [
        'authorization',
        'cookie',
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'card_number',
        'cvv',
    ],

    'replacement' => '[REDACTED]',

    'capture_exception_message' => false,

    'headers' => [
        'accept',
        'content-type',
        'user-agent',
        'x-requested-with',
    ],

    'ignore_exceptions' => [],
];
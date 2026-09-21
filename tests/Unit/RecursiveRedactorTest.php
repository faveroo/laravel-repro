<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;

it('redacts sensitive keys recursively without changing the original array', function () {
    $data = [
        'email' => 'gabriel@example.com',
        'PASSWORD' => '123456',
        'profile' => [
            'name' => 'Gabriel',
            'token' => 'secret-token',
        ],
        'items' => [
            [
                'api_key' => 'secret-key',
                'description' => 'Item público',
            ],
        ],
        'token_count' => 3,
    ];

    $redactor = new RecursiveRedactor([
        'password',
        'token',
        'api_key',
    ]);

    $result = $redactor->redact($data);

    expect($result)->toBe([
        'email' => 'gabriel@example.com',
        'PASSWORD' => '[REDACTED]',
        'profile' => [
            'name' => 'Gabriel',
            'token' => '[REDACTED]',
        ],
        'items' => [
            [
                'api_key' => '[REDACTED]',
                'description' => 'Item público',
            ],
        ],
        'token_count' => 3,
    ])->and($data)->toBe([
        'email' => 'gabriel@example.com',
        'PASSWORD' => '123456',
        'profile' => [
            'name' => 'Gabriel',
            'token' => 'secret-token',
        ],
        'items' => [
            [
                'api_key' => 'secret-key',
                'description' => 'Item público',
            ],
        ],
        'token_count' => 3,
    ]);
});

it('supports a custom replacement value', function () {
    $redactor = new RecursiveRedactor(
        sensitiveKeys: ['password'],
        replacement: '********',
    );

    $result = $redactor->redact([
        'username' => 'gabriel',
        'password' => 'secret',
    ]);

    expect($result)->toBe([
        'username' => 'gabriel',
        'password' => '********',
    ]);
});
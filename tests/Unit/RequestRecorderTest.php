<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Recording\RequestRecorder;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;
use Illuminate\Http\Request;

it('captures a sanitized reproduction case from a request', function () {
    $redactor = new RecursiveRedactor(
        sensitiveKeys: [
            'password',
            'token',
            'authorization',
        ],
    );

    $recorder = new RequestRecorder(
        redactor: $redactor,
        basePath: dirname(__DIR__, 2),
        capturedHeaders: [
            'accept',
            'user-agent',
        ],
        captureExceptionMessage: false,
    );

    $request = Request::create(
        uri: '/api/orders?source=mobile',
        method: 'POST',
        parameters: [
            'email' => 'gabriel@example.com',
            'password' => 'secret',
            'profile' => [
                'token' => 'private-token',
            ],
        ],
        server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'Pest',
            'HTTP_AUTHORIZATION' => 'Bearer secret-token',
        ],
    );

    $case = $recorder->capture(
        request: $request,
        throwable: new RuntimeException(
            'Private database information',
        ),
    );

    expect($case->method)->toBe('POST')
        ->and($case->uri)->toBe('/api/orders')
        ->and($case->routeName)->toBeNull()
        ->and($case->query)->toBe([
            'source' => 'mobile',
        ])
        ->and($case->payload['password'])->toBe('[REDACTED]')
        ->and($case->payload['profile']['token'])->toBe('[REDACTED]')
        ->and($case->headers)->toHaveKey('accept')
        ->and($case->headers)->not->toHaveKey('authorization')
        ->and($case->exception->message)->toBe('[OMITTED]')
        ->and($case->id)->toHaveLength(16);
});
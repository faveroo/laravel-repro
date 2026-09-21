<?php

declare(strict_types=1);
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;

it('creates a sanitized snapshot from a throwable', function () {
    $throwable = new RuntimeException('Database password is secret');

    $snapshot = ThrowableSnapshot::fromThrowable(
        throwable: $throwable,
        basePath: dirname(__DIR__, 2),
    );

    expect($snapshot->class)
        ->toBe(RuntimeException::class)
        ->and($snapshot->message)->toBe('[OMITTED]')
        ->and($snapshot->file)->not->toContain('\\')
        ->and($snapshot->file)->not->toContain('C:/Users/');
});

it('captures the exception message when explicitly enabled', function () {
    $throwable = new RuntimeException('Expected message');

    $snapshot = ThrowableSnapshot::fromThrowable(
        throwable: $throwable,
        basePath: dirname(__DIR__, 2),
        includeMessage: true,
    );

    expect($snapshot->message)->toBe('Expected message');
});
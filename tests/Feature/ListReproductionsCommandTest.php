<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

$makeCase = static function (
    string $id,
    string $capturedAt,
    string $uri,
    ?string $routeName = null,
): ReproductionCase {
    return new ReproductionCase(
        id: $id,
        capturedAt: new DateTimeImmutable($capturedAt),
        method: 'POST',
        uri: $uri,
        routeName: $routeName,
        headers: ['accept' => 'application/json'],
        query: [],
        payload: ['customer_id' => 42],
        exception: new ThrowableSnapshot(
            class: RuntimeException::class,
            message: '[OMITTED]',
            file: 'app/Services/OrderService.php',
            line: 84,
        ),
    );
};

beforeEach(function () {
    Storage::fake('local');

    config()->set('repro.disk', 'local');
    config()->set('repro.path', 'laravel-repro');
});

it('shows a message when there are no reproduction cases', function () {
    $this->artisan('repro:list')
        ->expectsOutputToContain(
            'No reproduction cases were found.',
        )
        ->assertSuccessful();
});

it('lists the newest reproduction cases respecting the limit', function () use ($makeCase) {
    $store = $this->app->make(ReproductionStore::class);

    $oldestCase = $makeCase(
        id: 'aaaaaaaaaaaaaaaa',
        capturedAt: '2026-09-20T12:00:00+00:00',
        uri: '/api/old-order',
        routeName: 'orders.old',
    );

    $newestCase = $makeCase(
        id: 'bbbbbbbbbbbbbbbb',
        capturedAt: '2026-09-22T15:30:00+00:00',
        uri: '/api/new-order',
        routeName: 'orders.new',
    );

    $store->save($oldestCase);
    $store->save($newestCase);

    $this->artisan('repro:list', [
        '--limit' => 1,
    ])->expectsTable(
        [
            'ID',
            'Captured at',
            'Method',
            'URI',
            'Route',
            'Exception',
        ],
        [
            [
                'bbbbbbbbbbbbbbbb',
                '2026-09-22 15:30:00+00:00',
                'POST',
                '/api/new-order',
                'orders.new',
                'RuntimeException',
            ],
        ],
    )->assertSuccessful();
});

it('rejects an invalid limit', function () {
    $this->artisan('repro:list', [
        '--limit' => 0,
    ])->expectsOutputToContain(
        'The --limit option must be a positive integer.',
    )->assertExitCode(Command::INVALID);
});
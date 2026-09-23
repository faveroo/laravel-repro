<?php

use Faveroo\LaravelRepro\Exceptions\CorruptedReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;
use Faveroo\LaravelRepro\Storage\FileReproductionStore;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Storage;

it('File Reproduction Test', function () {
    Storage::fake('local');

    $store = new FileReproductionStore(
        filesystem: $this->app->make(FilesystemFactory::class),
        disk: 'local',
        path: 'laravel-repro'
    );

    $case = new ReproductionCase(
        id: '7fd91a35d201abcd',
        capturedAt: new DateTimeImmutable('2026-09-22T12:00:00+00:00'),
        method: 'POST',
        uri: '/api/orders',
        routeName: 'orders.store',
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

    $case2 = new ReproductionCase(
        id: '7fd91a35d0kj1hn2k31j',
        capturedAt: new DateTimeImmutable('2026-09-23T12:00:00+00:00'),
        method: 'POST',
        uri: '/api/orders',
        routeName: 'orders.store',
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

    $store->save($case);
    $store->save($case2);

    Storage::disk('local')->assertExists(
        'laravel-repro/7fd91a35d201abcd.json',
    );

    $cases = $store->all();

    expect(array_map(
        static fn (ReproductionCase $case): string => $case->id,
        $cases,
    ))->toBe([
        $case2->id,
        $case->id,
    ]);

    expect($store->find($case->id))->toEqual($case);

    expect($store->find('aaaaaaaaaaaaaaaa'))->toBeNull();

    expect($store->find('../../secret'))->toBeNull();
});

it('throws a specific exception for corrupted JSON', function () {
    Storage::fake('local');

    Storage::disk('local')->put(
        'laravel-repro/aaaaaaaaaaaaaaaa.json',
        '{"invalid":',
    );

    $store = new FileReproductionStore(
        filesystem: $this->app->make(FilesystemFactory::class),
        disk: 'local',
        path: 'laravel-repro',
    );

    expect(
        fn () => $store->find('aaaaaaaaaaaaaaaa'),
    )->toThrow(
        CorruptedReproductionCase::class,
        'Reproduction case [laravel-repro/aaaaaaaaaaaaaaaa.json] contains invalid data.',
    );
});

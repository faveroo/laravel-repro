<?php

use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;

test('serializes and restores a reproduction case', function () {
    $exception = new ThrowableSnapshot(
        class: DivisionByZeroError::class,
        message: 'Division by zero',
        file: 'app/Services/OrderService.php',
        line: 84,
    );

    $case = new ReproductionCase(
        id: '7fd91a35d201',
        capturedAt: new DateTimeImmutable('2026-09-21T13:30:00+00:00'),
        method: 'POST',
        uri: '/api/orders',
        routeName: 'orders.store',
        headers: ['content-type' => 'application/json'],
        query: [],
        payload: ['customer_id' => 42],
        exception: $exception,
    );

    $data = $case->toArray();
    $restored = ReproductionCase::fromArray($data);

    expect($restored)->toEqual($case);
});
<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;
use Faveroo\LaravelRepro\Testing\PestTestGenerator;

it('generates a Pest regression test from a reproduction case', function () {
    $case = new ReproductionCase(
        id: '7fd91a35d201abcd',
        capturedAt: new DateTimeImmutable(
            '2026-09-22T15:30:00+00:00',
        ),
        method: 'POST',
        uri: '/api/orders',
        routeName: 'orders.store',
        headers: [
            'accept' => 'application/json',
        ],
        query: [
            'source' => 'mobile',
            'coupon' => 'WELCOME 20',
        ],
        payload: [
            'customer_id' => 42,
            'password' => '[REDACTED]',
            'items' => [
                [
                    'product_id' => 10,
                    'quantity' => 2,
                ],
            ],
        ],
        exception: new ThrowableSnapshot(
            class: RuntimeException::class,
            message: 'Private database information',
            file: 'app/Services/OrderService.php',
            line: 84,
        ),
    );

    $generator = new PestTestGenerator();

    $code = $generator->generate($case);
    print_r($code);

    expect($code)
        ->toStartWith('<?php')
        ->toContain(
            "it('reproduces RuntimeException for POST /api/orders'",
        )
        ->toContain("'POST'")
        ->toContain(
            "'/api/orders?source=mobile&coupon=WELCOME%2020'",
        )
        ->toContain("'customer_id' => 42")
        ->toContain("'password' => '[REDACTED]'")
        ->toContain("'accept' => 'application/json'")
        ->toContain('$response->assertSuccessful();')
        ->not->toContain('array (')
        ->not->toContain('Private database information');
});

it('does not append a question mark when the query is empty', function () {
    $case = new ReproductionCase(
        id: 'aaaaaaaaaaaaaaaa',
        capturedAt: new DateTimeImmutable(
            '2026-09-22T15:30:00+00:00',
        ),
        method: 'GET',
        uri: '/api/users',
        routeName: 'users.index',
        headers: [],
        query: [],
        payload: [],
        exception: new ThrowableSnapshot(
            class: RuntimeException::class,
            message: '[OMITTED]',
            file: 'app/Http/Controllers/UserController.php',
            line: 20,
        ),
    );

    $code = (new PestTestGenerator())->generate($case);

    expect($code)
        ->toContain("'/api/users'")
        ->not->toContain('array (')
        ->not->toContain("'/api/users?'");
});
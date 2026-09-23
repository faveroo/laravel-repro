<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Contracts\TestGenerator;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;

$makeCase = static fn (): ReproductionCase => new ReproductionCase(
    id: '7fd91a35d201abcd',
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

it('fails when the reproduction case does not exist', function () {
    $this->mock(
        ReproductionStore::class,
        function (MockInterface $mock): void {
            $mock->shouldReceive('find')
                ->once()
                ->with('aaaaaaaaaaaaaaaa')
                ->andReturnNull();
        },
    );

    $this->artisan('repro:test', [
        'id' => 'aaaaaaaaaaaaaaaa',
    ])->expectsOutputToContain(
        'Reproduction case [aaaaaaaaaaaaaaaa] was not found.',
    )->assertExitCode(Command::FAILURE);
});

it('generates a Pest test file', function () use ($makeCase) {
    config()->set(
        'repro.test_path',
        'tests/Feature/Reproductions',
    );

    $case = $makeCase();
    $generatedCode = "<?php\n\nit('generated test');\n";

    $relativePath =
        'tests/Feature/Reproductions/Repro_'.$case->id.'Test.php';

    $absolutePath = $this->app->basePath($relativePath);

    $this->mock(
        ReproductionStore::class,
        function (MockInterface $mock) use ($case): void {
            $mock->shouldReceive('find')
                ->once()
                ->with($case->id)
                ->andReturn($case);
        },
    );

    $this->mock(
        TestGenerator::class,
        function (MockInterface $mock) use (
            $case,
            $generatedCode,
        ): void {
            $mock->shouldReceive('generate')
                ->once()
                ->with($case)
                ->andReturn($generatedCode);
        },
    );

    $this->mock(
        Filesystem::class,
        function (MockInterface $mock) use (
            $absolutePath,
            $generatedCode,
        ): void {
            $mock->shouldReceive('exists')
                ->once()
                ->with($absolutePath)
                ->andReturnFalse();

            $mock->shouldReceive('ensureDirectoryExists')
                ->once()
                ->with(dirname($absolutePath));

            $mock->shouldReceive('put')
                ->once()
                ->with($absolutePath, $generatedCode);
        },
    );

    $this->artisan('repro:test', [
        'id' => $case->id,
    ])->expectsOutputToContain(
        sprintf('Test generated: %s', $relativePath),
    )->assertSuccessful();
});

it('does not overwrite an existing test without force', function () use ($makeCase) {
    $case = $makeCase();

    $relativePath =
        'tests/Feature/Reproductions/Repro_'.$case->id.'Test.php';

    $absolutePath = $this->app->basePath($relativePath);

    $this->mock(
        ReproductionStore::class,
        function (MockInterface $mock) use ($case): void {
            $mock->shouldReceive('find')
                ->once()
                ->andReturn($case);
        },
    );

    $this->mock(
        Filesystem::class,
        function (MockInterface $mock) use ($absolutePath): void {
            $mock->shouldReceive('exists')
                ->once()
                ->with($absolutePath)
                ->andReturnTrue();

            $mock->shouldNotReceive('ensureDirectoryExists');
            $mock->shouldNotReceive('put');
        },
    );

    $this->artisan('repro:test', [
        'id' => $case->id,
    ])->expectsOutputToContain(
        sprintf(
            'Test [%s] already exists. Use --force to overwrite it.',
            $relativePath,
        ),
    )->assertExitCode(Command::FAILURE);
});

it('overwrites an existing test with force', function () use ($makeCase) {
    $case = $makeCase();
    $generatedCode = "<?php\n";

    $relativePath =
        'tests/Feature/Reproductions/Repro_'.$case->id.'Test.php';

    $absolutePath = $this->app->basePath($relativePath);

    $this->mock(
        ReproductionStore::class,
        fn (MockInterface $mock) => $mock
            ->shouldReceive('find')
            ->once()
            ->andReturn($case),
    );

    $this->mock(
        TestGenerator::class,
        fn (MockInterface $mock) => $mock
            ->shouldReceive('generate')
            ->once()
            ->andReturn($generatedCode),
    );

    $this->mock(
        Filesystem::class,
        function (MockInterface $mock) use (
            $absolutePath,
            $generatedCode,
        ): void {
            $mock->shouldReceive('exists')
                ->once()
                ->andReturnTrue();

            $mock->shouldReceive('ensureDirectoryExists')
                ->once()
                ->with(dirname($absolutePath));

            $mock->shouldReceive('put')
                ->once()
                ->with($absolutePath, $generatedCode);
        },
    );

    $this->artisan('repro:test', [
        'id' => $case->id,
        '--force' => true,
    ])->assertSuccessful();
});
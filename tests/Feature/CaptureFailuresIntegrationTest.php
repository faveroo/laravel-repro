<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Middleware\CaptureFailures;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('captures a Laravel route failure', function () {
    Storage::fake('local');

    config()->set('repro.enabled', true);
    config()->set('repro.disk', 'local');
    config()->set('repro.path', 'laravel-repro');

    Route::middleware(CaptureFailures::class)
        ->get('/repro-test-failure', static function (): never {
            throw new RuntimeException('Test failure');
        })
        ->name('repro.test.failure');

    $this->withoutExceptionHandling();

    expect(
        fn () => $this->getJson('/repro-test-failure'),
    )->toThrow(
        RuntimeException::class,
        'Test failure',
    );

    $store = $this->app->make(ReproductionStore::class);
    $cases = $store->all();

    expect($cases)
        ->toHaveCount(1)
        ->and($cases[0]->method)->toBe('GET')
        ->and($cases[0]->uri)->toBe('/repro-test-failure')
        ->and($cases[0]->routeName)->toBe('repro.test.failure')
        ->and($cases[0]->exception->class)->toBe(RuntimeException::class)
        ->and($cases[0]->exception->message)->toBe('[OMITTED]');

    Storage::disk('local')->assertExists(
        'laravel-repro/'.$cases[0]->id.'.json',
    );
});

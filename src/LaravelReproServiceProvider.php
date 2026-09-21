<?php

namespace Faveroo\LaravelRepro;

use Faveroo\LaravelRepro\Contracts\Redactor;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;
use Illuminate\Support\ServiceProvider;

final class LaravelReproServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/repro.php',
            'repro',
        );

        $this->app->singleton(
            Redactor::class,
            function ($app): Redactor {
                return new RecursiveRedactor(
                    sensitiveKeys: $app['config']->get('repro.redact', []),
                    replacement: $app['config']->get(
                        'repro.replacement',
                        '[REDACTED]'
                    ),
                );
            },
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/repro.php' => $this->app->configPath('repro.php'),
        ], 'repro-config');
    }
}
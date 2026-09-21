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

        $this->app->singleton(
            RequestRecorder::class,
            function ($app): RequestRecorder {
                return new RequestRecorder(
                    redactor: $app->make(Redactor::class),
                    basePath: $app->basePath(),
                    capturedHeaders: $app['config']->get('repro.headers', []),
                    captureExceptionMessage: (bool) $app['config']->get(
                        'repro.capture_exception_message',
                        false,
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
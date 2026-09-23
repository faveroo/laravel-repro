<?php

namespace Faveroo\LaravelRepro;

use Faveroo\LaravelRepro\Commands\GenerateTestCommand;
use Faveroo\LaravelRepro\Commands\ListReproductionCommand;
use Faveroo\LaravelRepro\Contracts\Redactor;
use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Contracts\TestGenerator;
use Faveroo\LaravelRepro\Recording\RequestRecorder;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;
use Faveroo\LaravelRepro\Storage\FileReproductionStore;
use Faveroo\LaravelRepro\Testing\PestTestGenerator;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
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
                    capturedHeaders: (array) $app['config']->get('repro.headers', []),
                    captureExceptionMessage: (bool) $app['config']->get(
                        'repro.capture_exception_message',
                        false,
                    ),
                );
            },
        );

        $this->app->singleton(
            ReproductionStore::class,
            function ($app): FileReproductionStore {
                return new FileReproductionStore(
                    filesystem: $app->make(FilesystemFactory::class),
                    disk: (string) $app['config']->get(
                        'repro.disk',
                        'local'
                    ),
                    path: (string) $app['config']->get(
                        'repro.path',
                        'laravel-repro'
                    ),
                );
            },
        );

        $this->app->singleton(
            TestGenerator::class,
            PestTestGenerator::class,
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/repro.php' => $this->app->configPath('repro.php'),
        ], 'repro-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListReproductionCommand::class,
                GenerateTestCommand::class,
            ]);
        }
    }
}

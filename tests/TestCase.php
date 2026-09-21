<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Tests;

use Faveroo\LaravelRepro\LaravelReproServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelReproServiceProvider::class,
        ];
    }
}

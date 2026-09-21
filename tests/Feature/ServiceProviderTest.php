<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\Redactor;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;

it('registers the redactor as a singleton', function () {
    $firstInstance = $this->app->make(Redactor::class);
    $secondInstance = $this->app->make(Redactor::class);

    expect($firstInstance)
        ->toBeInstanceOf(RecursiveRedactor::class)
        ->and($secondInstance)
        ->toBe($firstInstance);
});
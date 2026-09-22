<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\Redactor;
use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;
use Faveroo\LaravelRepro\Recording\RequestRecorder;
use Faveroo\LaravelRepro\Storage\FileReproductionStore;

it('registers the redactor as a singleton', function () {
    $firstInstance = $this->app->make(Redactor::class);
    $secondInstance = $this->app->make(Redactor::class);

    expect($firstInstance)
        ->toBeInstanceOf(RecursiveRedactor::class)
        ->and($secondInstance)
        ->toBe($firstInstance);
});

it('registers the request recorder as a singleton', function () {
    $firstInstance = $this->app->make(RequestRecorder::class);
    $secondInstance = $this->app->make(RequestRecorder::class);

    expect($firstInstance)
        ->toBeInstanceOf(RequestRecorder::class)
        ->and($secondInstance)
        ->toBe($firstInstance);
});

it('registers the reproduction store as a singleton', function () {
    $firstInstance = $this->app->make(ReproductionStore::class);
    $secondInstance = $this->app->make(ReproductionStore::class);

    expect($firstInstance)
        ->toBeInstanceOf(FileReproductionStore::class)
        ->and($secondInstance)
        ->toBe($firstInstance);
});
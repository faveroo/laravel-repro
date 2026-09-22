<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Contracts;

use Faveroo\LaravelRepro\Reproduction\ReproductionCase;

interface TestGenerator
{
    public function generate(ReproductionCase $case): string;
}
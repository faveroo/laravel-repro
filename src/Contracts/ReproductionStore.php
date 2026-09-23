<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Contracts;

use Faveroo\LaravelRepro\Reproduction\ReproductionCase;

interface ReproductionStore
{
    public function save(ReproductionCase $case): void;

    public function find(string $id): ?ReproductionCase;

    /**
     * @return list<ReproductionCase>
     */
    public function all(): array;
}

<?php

namespace Faveroo\LaravelRepro\Contracts;

interface Redactor
{
    /**
     * @param array<array-key, mixed $data
     * @return array<array-key, mixed
     */
    public function redact(array $data): array;
}

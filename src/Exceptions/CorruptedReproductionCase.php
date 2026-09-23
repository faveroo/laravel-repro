<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Exceptions;

use RuntimeException;
use Throwable;

final class CorruptedReproductionCase extends RuntimeException
{
    public static function at(
        string $filename,
        Throwable $previous
    ): self {
        return new self(
            message: sprintf(
                'Reproduction case [%s] contains invalid data.',
                $filename,
            ),
            previous: $previous
        );
    }
}

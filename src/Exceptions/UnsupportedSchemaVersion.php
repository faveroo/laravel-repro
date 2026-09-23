<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Exceptions;

use RuntimeException;

final class UnsupportedSchemaVersion extends RuntimeException
{
    public static function for(
        mixed $actual,
        int $expected,
    ): self {
        $actualValue = is_scalar($actual) || $actual === null
            ? var_export($actual, true)
            : get_debug_type($actual);

        return new self(
            sprintf(
                'Unsupported reproduction schema version [%s]. Expected [%d].',
                $actualValue,
                $expected
            )
        );
    }
}

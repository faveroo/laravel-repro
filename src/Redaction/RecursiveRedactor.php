<?php

namespace Faveroo\LaravelRepro\Redaction;

use Faveroo\LaravelRepro\Contracts\Redactor;

final readonly class RecursiveRedactor implements Redactor
{
    /**
     * @var list<string>
     */
    private array $sensitiveKeys;

    public function __construct(
        array $sensitiveKeys,
        private string $replacement = '[REDACTED]',
    ) {
        $this->sensitiveKeys = array_map(
            static fn (string $key): string => strtolower($key),
            $sensitiveKeys
        );
    }

    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = $this->replacement;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(
            strtolower($key),
            $this->sensitiveKeys,
            true,
        );
    }
}

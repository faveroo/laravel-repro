<?php

namespace Faveroo\LaravelRepro\Reproduction;

use Throwable;

final readonly class ThrowableSnapshot
{
    public function __construct(
        public string $class,
        public string $message,
        public string $file,
        public int $line,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            class: $data['class'],
            message: $data['message'],
            file: $data['file'],
            line: $data['line'],
        );
    }

    public static function fromThrowable(
        Throwable $throwable,
        string $basePath,
        bool $includeMessage = false,
    ): self {
        return new self(
            class: $throwable::class,
            message: $includeMessage ? $throwable->getMessage() : '[OMITTED]',
            file: self::relativePath(
                path: $throwable->getFile(),
                basePath: $basePath
            ),
            line: $throwable->getLine(),
        );
    }

    private static function relativePath(
        string $path,
        string $basePath,
    ): string {
        $normalizedPath = str_replace('\\', '/', $path);

        $normalizedBasePath = rtrim(
            str_replace('\\', '/', $basePath),
            '/',
        ).'/';

        if (str_starts_with($normalizedPath, $normalizedBasePath)) {
            return substr(
                $normalizedPath,
                strlen($normalizedBasePath),
            );
        }

        return basename($normalizedPath);
    }
}

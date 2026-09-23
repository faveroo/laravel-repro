<?php

namespace Faveroo\LaravelRepro\Reproduction;

use DateTimeImmutable;
use Faveroo\LaravelRepro\Exceptions\UnsupportedSchemaVersion;

final readonly class ReproductionCase
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public DateTimeImmutable $capturedAt,
        public string $method,
        public string $uri,
        public ?string $routeName,
        public array $headers,
        public array $query,
        public array $payload,
        public ThrowableSnapshot $exception,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'id' => $this->id,
            'captured_at' => $this->capturedAt->format(DATE_ATOM),
            'method' => $this->method,
            'uri' => $this->uri,
            'route_name' => $this->routeName,
            'headers' => $this->headers,
            'query' => $this->query,
            'payload' => $this->payload,
            'exception' => $this->exception->toArray(),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $schemaVersion = $data['schema_version'] ?? null;

        if ($schemaVersion !== self::SCHEMA_VERSION) {
            throw UnsupportedSchemaVersion::for(
                actual: $schemaVersion,
                expected: self::SCHEMA_VERSION
            );
        }

        return new self(
            id: $data['id'],
            capturedAt: new DateTimeImmutable($data['captured_at']),
            method: $data['method'],
            uri: $data['uri'],
            routeName: $data['route_name'] ?? null,
            headers: $data['headers'],
            query: $data['query'],
            payload: $data['payload'],
            exception: ThrowableSnapshot::fromArray($data['exception']),
        );
    }
}

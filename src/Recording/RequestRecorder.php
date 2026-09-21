<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Recording;

use DateTimeImmutable;
use DateTimeZone;
use Faveroo\LaravelRepro\Contracts\Redactor;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Faveroo\LaravelRepro\Reproduction\ThrowableSnapshot;
use Illuminate\Http\Request;
use Throwable;

final readonly class RequestRecorder
{
    /**
     * @param list<string> $capturedHeaders
     */
    public function __construct(
        private Redactor $redactor,
        private string $basePath,
        private array  $capturedHeaders,
        private bool $captureExceptionMessage = false
    ) {}

    public function capture(
        Request $request,
        Throwable $throwable
    ): ReproductionCase {
        return new ReproductionCase(
            id: bin2hex(random_bytes(8)),
            capturedAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            method: strtoupper($request->getMethod()),
            uri: $request->getPathInfo(),
            routeName: $this->routeName($request),
            headers: $this->redactor->redact(
                $this->captureHeaders($request)
            ),
            query: $this->redactor->redact(
                $request->query->all(),
            ),
            payload: $this->redactor->redact(
                $this->capturePayload($request)
            ),
            exception: ThrowableSnapshot::fromThrowable(
                throwable: $throwable,
                basePath: $this->basePath,
                includeMessage: $this->captureExceptionMessage
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private function captureHeaders(Request $request): array
    {
        $headers = [];

        foreach ($this->capturedHeaders as $header) {
            if (! $request->headers->has($header)) {
                continue;
            }

            $headers[strtolower($header)] = (string) $request->headers->get($header);
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function capturePayload(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        return $request->request->all();
    }

    private function routeName(Request $request): ?string
    {
        $route = $request->route();

        if (! is_object($route) || ! method_exists($route, 'getName')) {
            return null;
        }

        return $route->getName();
    }
}
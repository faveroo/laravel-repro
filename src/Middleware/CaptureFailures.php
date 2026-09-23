<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Middleware;

use Closure;
use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Recording\RequestRecorder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Throwable;

final readonly class CaptureFailures
{
    public function __construct(
        private RequestRecorder $recorder,
        private ReproductionStore $store,
        private Repository $config
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->shouldCapture()) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            $this->capture($request, $throwable);

            throw $throwable;
        }

        $exception = is_object($response)
            ? ($response->exception ?? null)
            : null;

        if ($exception instanceof Throwable) {
            $this->capture($request, $exception);
        }

        return $response;
    }

    private function capture(
        Request $request,
        Throwable $throwable
    ): void {
        if ($this->shouldIgnore($throwable)) {
            return;
        }

        try {
            $case = $this->recorder->capture(
                request: $request,
                throwable: $throwable,
            );

            $this->store->save($case);
        } catch (Throwable) {

        }
    }

    private function shouldCapture(): bool
    {
        if (! $this->config->get('repro.enabled', false)) {
            return false;
        }

        if (
            app()->environment('testing')
            && ! $this->config->get('repro.capture_in_testing', false)
        ) {
            return false;
        }

        return true;
    }

    private function shouldIgnore(Throwable $throwable): bool
    {
        foreach ($this->config->get('repro.ignore_exceptions', []) as $exceptionClass) {
            if (is_a($throwable, $exceptionClass)) {
                return true;
            }
        }

        return false;
    }
}

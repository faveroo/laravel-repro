<?php

declare(strict_types=1);

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Middleware\CaptureFailures;
use Faveroo\LaravelRepro\Recording\RequestRecorder;
use Faveroo\LaravelRepro\Redaction\RecursiveRedactor;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CaptureFailuresInMemoryStore implements ReproductionStore
{
    /** @var array<string, ReproductionCase> */
    private array $cases = [];

    public function __construct(
        private readonly bool $failOnSave = false,
    ) {}

    public function save(ReproductionCase $case): void
    {
        if ($this->failOnSave) {
            throw new RuntimeException('Storage failure');
        }

        $this->cases[$case->id] = $case;
    }

    public function find(string $id): ?ReproductionCase
    {
        return $this->cases[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->cases);
    }
}

$makeMiddleware = static function (
    array $config,
    ReproductionStore $store,
): CaptureFailures {
    $redactor = new RecursiveRedactor([
        'password',
        'token',
    ]);

    $recorder = new RequestRecorder(
        redactor: $redactor,
        basePath: dirname(__DIR__, 2),
        capturedHeaders: ['accept'],
        captureExceptionMessage: false,
    );

    return new CaptureFailures(
        recorder: $recorder,
        store: $store,
        config: new ConfigRepository([
            'repro' => $config,
        ]),
    );
};

it('captures and rethrows the original exception', function () use ($makeMiddleware) {
    $store = new CaptureFailuresInMemoryStore;

    $middleware = $makeMiddleware([
        'enabled' => true,
        'ignore_exceptions' => [],
    ], $store);

    $request = Request::create(
        uri: '/api/orders',
        method: 'POST',
        parameters: [
            'password' => 'secret',
        ],
        server: [
            'HTTP_ACCEPT' => 'application/json',
        ],
    );

    $original = new RuntimeException('Private error');
    $caught = null;

    try {
        $middleware->handle(
            $request,
            static fn (): never => throw $original,
        );
    } catch (Throwable $throwable) {
        $caught = $throwable;
    }

    $cases = $store->all();

    expect($caught)
        ->toBe($original)
        ->and($cases)->toHaveCount(1)
        ->and($cases[0]->payload['password'])->toBe('[REDACTED]')
        ->and($cases[0]->exception->message)->toBe('[OMITTED]');
});

it('does not capture when the package is disabled', function () use ($makeMiddleware) {
    $store = new CaptureFailuresInMemoryStore;

    $middleware = $makeMiddleware([
        'enabled' => false,
        'ignore_exceptions' => [],
    ], $store);

    $original = new RuntimeException('Application failure');
    $caught = null;

    try {
        $middleware->handle(
            Request::create('/failure'),
            static fn (): never => throw $original,
        );
    } catch (Throwable $throwable) {
        $caught = $throwable;
    }

    expect($caught)
        ->toBe($original)
        ->and($store->all())->toBe([]);
});

it('does not capture ignored exceptions', function () use ($makeMiddleware) {
    $store = new CaptureFailuresInMemoryStore;

    $middleware = $makeMiddleware([
        'enabled' => true,
        'ignore_exceptions' => [
            RuntimeException::class,
        ],
    ], $store);

    $original = new RuntimeException('Ignored failure');
    $caught = null;

    try {
        $middleware->handle(
            Request::create('/failure'),
            static fn (): never => throw $original,
        );
    } catch (Throwable $throwable) {
        $caught = $throwable;
    }

    expect($caught)
        ->toBe($original)
        ->and($store->all())->toBe([]);
});

it('preserves the original exception when storage fails', function () use ($makeMiddleware) {
    $store = new CaptureFailuresInMemoryStore(
        failOnSave: true,
    );

    $middleware = $makeMiddleware([
        'enabled' => true,
        'ignore_exceptions' => [],
    ], $store);

    $original = new RuntimeException('Original application failure');
    $caught = null;

    try {
        $middleware->handle(
            Request::create('/failure'),
            static fn (): never => throw $original,
        );
    } catch (Throwable $throwable) {
        $caught = $throwable;
    }

    expect($caught)
        ->toBe($original)
        ->and($store->all())->toBe([]);
});

it('captures an exception attached to a rendered response', function () use ($makeMiddleware) {
    $store = new CaptureFailuresInMemoryStore;

    $middleware = $makeMiddleware([
        'enabled' => true,
        'ignore_exceptions' => [],
    ], $store);

    $request = Request::create('/failure');

    $exception = new RuntimeException(
        'Rendered application failure',
    );

    $response = new Response(
        content: 'Internal Server Error',
        status: 500,
    );

    $response->withException($exception);

    $returnedResponse = $middleware->handle(
        $request,
        static fn (): Response => $response,
    );

    $cases = $store->all();

    expect($returnedResponse)
        ->toBe($response)
        ->and($cases)->toHaveCount(1)
        ->and($cases[0]->exception->class)
        ->toBe(RuntimeException::class)
        ->and($cases[0]->exception->message)
        ->toBe('[OMITTED]');
});

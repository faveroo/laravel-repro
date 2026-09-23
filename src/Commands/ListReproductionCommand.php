<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Commands;

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Illuminate\Console\Command;

final class ListReproductionCommand extends Command
{
    protected $signature = 'repro:list
                            {--limit=20 : Maximum number of cases to display}';

    protected $description = 'List captured Laravel reproduction cases';

    public function __construct(
        private readonly ReproductionStore $store
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = filter_var(
            $this->option('limit'),
            FILTER_VALIDATE_INT,
            options: [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if ($limit === false) {
            $this->components->error(
                'The --limit option must be a positive integer.',
            );

            return self::INVALID;
        }

        $cases = array_slice(
            $this->store->all(),
            0,
            $limit,
        );

        if ($cases === []) {
            $this->components->error(
                'No reproduction cases were found.',
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'ID',
                'Captured at',
                'Method',
                'URI',
                'Route',
                'Exception',
            ],
            array_map(
                static fn (ReproductionCase $case): array => [
                    $case->id,
                    $case->capturedAt->format('Y-m-d H:i:sP'),
                    $case->method,
                    $case->uri,
                    $case->routeName ?? '-',
                    self::shortClassName($case->exception->class),
                ],
                $cases,
            ),
        );

        return self::SUCCESS;
    }

    private static function shortClassName(string $class): string
    {
        $normalizedClass = str_replace('\\', '/', $class);

        return basename($normalizedClass);
    }
}

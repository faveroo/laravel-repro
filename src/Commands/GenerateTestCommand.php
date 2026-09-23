<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Commands;

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Contracts\TestGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

final class GenerateTestCommand extends Command
{
    protected $signature = 'repro:test
                            {id : Reproduction case identifier}
                            {--force : Overwrite the test if it already exists}';

    protected $description = 'Generate a Pest regression test from a reproduction case';

    public function __construct(
        private readonly ReproductionStore $store,
        private readonly TestGenerator $generator,
        private readonly Filesystem $files,
        private readonly Application $app,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = (string) $this->argument('id');
        $case = $this->store->find($id);

        if ($case === null) {
            $this->components->error(
                sprintf('Reproduction case [%s] was not found.', $id),
            );

            return self::FAILURE;
        }

        $relPath = $this->relativePath($case->id);
        $absPath = $this->app->basePath($relPath);

        if ($this->files->exists($absPath) && ! $this->option('force')) {
            $this->components->error(
                sprintf(
                    'Test [%s] already exists. Use --force to overwrite it.',
                    $relPath
                ),
            );

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(
            dirname($absPath),
        );

        $this->files->put(
            $absPath,
            $this->generator->generate($case),
        );

        $this->components->info(
            sprintf('Test generated: %s', $relPath),
        );

        return self::SUCCESS;
    }

    private function relativePath(string $id): string
    {
        $directory = trim(
            (string) config(
                'repro.test_path',
                'tests/Feature/Reproductions'
            ),
            '/\\',
        );

        return $directory.'/Repro_'.$id.'Test.php';
    }
}

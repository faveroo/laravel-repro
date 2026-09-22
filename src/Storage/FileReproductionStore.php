<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Storage;

use Faveroo\LaravelRepro\Contracts\ReproductionStore;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

final readonly class FileReproductionStore implements ReproductionStore
{
    private string $path;

    public function __construct(
        private FilesystemFactory $filesystem,
        private string $disk,
        string $path
    ) {
        $this->path = trim($path, '/');
    }

    public function save(ReproductionCase $case): void
    {
        $json = json_encode(
            $case->toArray(),
            JSON_PRETTY_PRINT 
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );

        $this->filesystem
            ->disk($this->disk)
            ->put($this->filename($case->id), $json);
    }

    public function find(string $id): ReproductionCase|null
    {
        if (! preg_match('/\A[a-f0-9]{16}\z/', $id)) {
            return null;
        }

        $disk = $this->filesystem->disk($this->disk);
        $filename = $this->filename($id);

        if (! $disk->exists($filename)) {
            return null;
        }

        return $this->read($filename);
    }

    public function all(): array
    {
        $disk = $this->filesystem->disk($this->disk);
        $cases = [];

        foreach ($disk->files($this->path) as $file) {
            if (! str_ends_with($file, '.json')) {
                continue;
            }

            $case = $this->read($file);

            if ($case !== null) {
                $cases[] = $case; 
            }
        }

        usort(
            $cases,
            static fn (
                ReproductionCase $first,
                ReproductionCase $second,
            ): int => $second->capturedAt <=> $first->capturedAt,
        );

        return $cases;
    }

    private function filename(string $id): string
    {
        $filename = $id.'.json';

        if ($this->path === '') {
            return $filename;
        }

        return $this->path.'/'.$filename;
    }

    private function read(string $filename): ?ReproductionCase
    {
        $contents = $this->filesystem
            ->disk($this->disk)
            ->get($filename);

        $data = json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($data)) {
            return null;
        }

        return ReproductionCase::fromArray($data);
    }
}
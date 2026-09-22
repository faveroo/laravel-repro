<?php

declare(strict_types=1);

namespace Faveroo\LaravelRepro\Testing;

use Faveroo\LaravelRepro\Contracts\TestGenerator;
use Faveroo\LaravelRepro\Reproduction\ReproductionCase;
use SebastianBergmann\Exporter\ExportContext;

final class PestTestGenerator implements TestGenerator
{
    public function generate(ReproductionCase $case): string
    {
        $template = <<<'PHP'
<?php

declare(strict_type=1);

it(%s, function() {
    $response = $this->json(
        %s,
        %s,
        %s,
        %s,
    );

    // Adjust this assertion to the expected behavior after fixing the bug.
    $response->assertSuccessful();
});

PHP;

        return sprintf(
            $template,
            $this->export($this->description($case)),
            $this->export($case->method),
            $this->export($this->uri($case)),
            $this->export($case->payload, 2),
            $this->export($case->headers, 2),
        ).PHP_EOL;


    }

    private function uri(ReproductionCase $case): string
    {
        if ($case->query === []) {
            return $case->uri;
        }

        return $case->uri . '?' . http_build_query(
            $case->query,
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
    }

    private function description(ReproductionCase $case): string
    {
        $exception = basename(
            str_replace('\\', '/', $case->exception->class),
        );

        return sprintf(
            'reproduces %s for %s %s',
            $exception,
            $case->method,
            $case->uri
        );
    }

    private function export(mixed $value, int $level = 0): string
    {

        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $level);
        $itemIndent = str_repeat('    ', $level + 1);
        $isList = array_is_list($value);
        $lines = [];

        foreach ($value as $key => $item) {
            $keyCode = $isList
                ? ''
                : $this->export($key).' => ';

            $lines[] = $itemIndent
                .$keyCode
                .$this->export($item, $level + 1)
                .',';
        }

        return '['
            .PHP_EOL
            .implode(PHP_EOL, $lines)
            .PHP_EOL
            .$indent
            .']';
    }
}
<?php

declare(strict_types=1);

namespace NotSoSimple\Reports;

final class NullReport extends Report
{
    /**
     * Generate no report.
     *
     * @param string $file
     * @param array $problems
     * @return void
     *
     * @psalm-param array<\NotSoSimple\DataObjects\Problem> $problems
     */
    public function generate(string $file, array $problems): void
    {
    }
}

<?php declare(strict_types=1);

namespace NotSoSimple\Reports;

use NotSoSimple\DataObjects\Problem;
use NotSoSimple\Writer;

final class JsonReport
{
    /**
     * Generate a JSON report.
     *
     * @param string $file
     * @param array $errors
     * @return void
     *
     * @psalm-param array<\NotSoSimple\DataObjects\Problem> $errors
     */
    public static function generate(string $file, array $errors): void
    {
        $toArray = array_map(static function (Problem $problem): array {
            return $problem->toArray();
        }, $errors);

        $json = json_encode($toArray, JSON_PRETTY_PRINT);

        file_put_contents($file, $json);

        Writer::writeln("Successfully wrote JSON report to <comment>{$file}</comment>.");
    }
}

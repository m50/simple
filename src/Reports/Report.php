<?php

declare(strict_types=1);

namespace NotSoSimple\Reports;

abstract class Report
{
    public static function getFormat(string $reportFormat): Report
    {
        switch (strtolower($reportFormat)) {
            case 'json':
                return new JsonReport();
            case 'junit':
                return new JUnitReport();
            case 'html':
                return new HtmlReport();
            default:
            case 'null':
                return new NullReport();
        }
    }

    /**
     * @param string $file
     * @param array<\NotSoSimple\DataObjects\Problem> $problems
     * @return void
     *
     * @psalm-param list<\NotSoSimple\DataObjects\Problem> $problems
     */
    public abstract function generate(string $file, array $problems): void;
}

<?php

declare(strict_types=1);

namespace NotSoSimple\Reports;

use NotSoSimple\DataObjects\Problem;

final class HtmlReport extends Report
{
    private const HIGHLIGHT_CLASS = 'text-red-600 font-bold';

    private string $sectionTpl = '';

    /**
     * Generate an HTML report.
     *
     * @param string $file
     * @param array $problems
     * @return void
     *
     * @psalm-param array<\NotSoSimple\DataObjects\Problem> $problems
     */
    public function generate(string $file, array $problems): void
    {
        $template = file_get_contents(__DIR__ . '/../../templates/report.tpl.html');
        $this->sectionTpl = file_get_contents(__DIR__ . '/../../templates/section.tpl.html');

        $body = '';
        /** @var Problem $error */
        foreach ($problems as $error) {
            $body .= $this->createSection($error);
        }

        $template = str_replace('{{ $body }}', $body, $template);

        file_put_contents($file, $template);
    }

    private function createSection(Problem $problem): string
    {
        $eTpl = str_replace('{{ $line }}', $this->buildLine($problem->line()), $this->sectionTpl);
        $eTpl = str_replace('{{ $fileName }}', $problem->fileName(), $eTpl);
        $eTpl = str_replace('{{ $key }}', $problem->key(), $eTpl);

        $color = $problem->weight() < 3 ? 'yellow' : 'red';
        $eTpl = str_replace('{{ $color }}', $color, $eTpl);

        $eTpl = str_replace('{{ $lineNumber }}', (string) $problem->lineNumber(), $eTpl);
        $eTpl = str_replace('{{ $weight }}', (string) $problem->weight(), $eTpl);

        return $eTpl;
    }

    private function buildLine(string $line): string
    {
        $line = htmlentities($line);
        $line = str_replace(htmlentities('<fg=red>'), '<span class="' . self::HIGHLIGHT_CLASS . '">', $line);
        $line = str_replace(htmlentities('</>'), '</span>', $line);

        return $line;
    }
}

<?php declare(strict_types=1);

namespace NotSoSimple\Reports;

use NotSoSimple\DataObjects\Problem;

final class HtmlReport
{
    private const HIGHLIGHT_CLASS = 'text-red-600 font-bold';

    private static string $sectionTpl = '';

    /**
     * Generate an HTML report.
     *
     * @param string $file
     * @param array $errors
     * @return void
     *
     * @psalm-param array<\NotSoSimple\DataObjects\Problem> $errors
     */
    public static function generate(string $file, array $errors): void
    {
        $template = file_get_contents(__DIR__ . '/../../templates/report.tpl.html');
        self::$sectionTpl = file_get_contents(__DIR__ . '/../../templates/section.tpl.html');

        $body = '';
        /** @var Problem $error */
        foreach ($errors as $error) {
            $body .= self::createSection($error);
        }

        $template = str_replace('{{ $body }}', $body, $template);

        file_put_contents($file, $template);
    }

    private static function createSection(Problem $error): string
    {
        $eTpl = str_replace('{{ $line }}', self::buildLine($error->line()), self::$sectionTpl);
        $eTpl = str_replace('{{ $fileName }}', $error->fileName(), $eTpl);
        $eTpl = str_replace('{{ $key }}', $error->key(), $eTpl);

        $eTpl = str_replace('{{ $lineNumber }}', (string) $error->lineNumber(), $eTpl);
        $eTpl = str_replace('{{ $weight }}', (string) $error->weight(), $eTpl);

        return $eTpl;
    }

    private static function buildLine(string $line): string
    {
        $line = htmlentities($line);
        $line = str_replace(htmlentities('<fg=red>'), '<span class="' . self::HIGHLIGHT_CLASS . '">', $line);
        $line = str_replace(htmlentities('</>'), '</span>', $line);

        return $line;
    }
}

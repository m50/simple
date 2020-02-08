<?php declare(strict_types=1);

namespace NotSoSimple\Reports;

final class HtmlReport
{
    public static function generate(string $file, array $errors): void
    {
        $template = file_get_contents('../../tempaltes/report.tpl.html');
        $sectionTpl = file_get_contents('../../tempaltes/section.tpl.html');
    }
}

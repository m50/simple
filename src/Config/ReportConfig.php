<?php declare(strict_types=1);

namespace NotSoSimple\Config;

use NotSoSimple\Exceptions\UnableToLoadConfigException;

/** @psalm-immutable */
final class ReportConfig implements ConfigInterface
{
    protected string $format;
    protected string $output;

    public function __construct(string $output, string $format = '')
    {
        $this->output = $output;
        $this->format = $format;

        if (empty($format) && ! empty($output)) {
            $this->format = self::getReportFormat($output);
        }
    }

    public function format(): string
    {
        return $this->format;
    }

    public function output(): string
    {
        return $this->output;
    }

    /**
     * @psalm-mutation-free
     *
     * @return string[]|null
     *
     * @psalm-return array{format: string, output: string}
     */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'output' => $this->output,
        ];
    }

    /** @psalm-pure */
    public static function getReportFormat(string $reportFile): string
    {
        if (preg_match('/\.(json|xml|html)$/i', $reportFile, $matches)) {
            [,$reportFormat] = $matches;
            if ($reportFormat === 'xml') {
                $reportFormat = 'junit';
            }
        } else {
            throw new UnableToLoadConfigException('Unable to determine report format: Invalid extensions', 19);
        }

        $reportFormat = strtolower($reportFormat);

        return $reportFormat;
    }
}

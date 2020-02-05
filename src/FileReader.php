<?php declare(strict_types=1);

namespace NotSoSimple;

use NotSoSimple\DataObjects\Problem;

final class FileReader
{
    private string $fileName;

    /** @var array<\NotSoSimple\Config\ProblemConfig> */
    private static array $problemConfigs = [];

    /** @var array<string> */
    private array $lines = [];

    private Config $config;

    /** @var array<Problem> */
    private array $problems = [];

    /**
     * @param array<string> $lines
     */
    public function __construct(string $fileName, array $lines, Config $config)
    {
        $this->fileName = $fileName;
        $this->lines = $lines;
        $this->config = $config;
    }

    public function read(): self
    {
        $lineNumber = 0;
        foreach ($this->lines as $line) {
            $lineNumber++;

            if (empty($line)) {
                continue;
            }

            $this->scanLine($line, $lineNumber);

            if ($this->config->shortcircuit() && count($this->problems) > 0) {
                break;
            }
        }

        return $this;
    }

    public function getErrors(): array
    {
        return $this->problems;
    }

    private function scanLine(string $line, int $lineNumber): void
    {
        if (empty(self::$problemConfigs)) {
            self::$problemConfigs = $this->config->getProblems();
        }

        foreach (self::$problemConfigs as $problemConfig) {
            if ($problemConfig->scanLine($line)) {
                $highlightedLine = preg_replace($problemConfig->regex(), "<fg=red>\\0</>", Writer::escape($line));
                $this->problems[] = new Problem(
                    $this->fileName,
                    $problemConfig->key(),
                    $problemConfig->weight(),
                    $highlightedLine,
                    $lineNumber
                );
                if ($this->config->shortcircuit()) {
                    break;
                }
            }
        }
    }
}

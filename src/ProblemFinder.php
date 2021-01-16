<?php

declare(strict_types=1);

namespace NotSoSimple;

use NotSoSimple\DataObjects\Problem;

final class ProblemFinder
{
    private string $fileName;

    /** @var list<\NotSoSimple\Config\ProblemConfig> */
    private static array $problemConfigs = [];

    /** @var list<string> */
    private array $lines = [];

    private Config $config;

    /**
     * @param list<string> $lines
     */
    public function __construct(string $fileName, array $lines, Config $config)
    {
        $this->fileName = $fileName;
        $this->lines = $lines;
        $this->config = $config;
    }

    /**
     * @return Problem[]
     *
     * @psalm-return list<Problem>
     */
    public function findErrors(): array
    {
        $lineNumber = 0;
        $problems = [];
        foreach ($this->lines as $line) {
            $lineNumber++;

            if (empty($line)) {
                continue;
            }

            $problems = array_merge($problems, $this->scanLine($line, $lineNumber));

            if ($this->config->shortcircuit() && count($problems) > 0) {
                break;
            }
        }

        return $problems;
    }

    /**
     * @return Problem[]
     *
     * @psalm-return list<Problem>
     */
    private function scanLine(string $line, int $lineNumber): array
    {
        if (empty(self::$problemConfigs)) {
            self::$problemConfigs = $this->config->getProblems();
        }

        $problems = [];

        foreach (self::$problemConfigs as $problemConfig) {
            if ($problemConfig->scanLine($line)) {
                $highlightedLine = preg_replace($problemConfig->regex(), "<fg=red>\\0</>", Writer::escape($line));
                $problems[] = new Problem(
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

        return $problems;
    }
}

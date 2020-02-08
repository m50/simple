<?php declare(strict_types=1);

namespace NotSoSimple\DataObjects;

use NotSoSimple\Writer;

/** @psalm-immutable */
final class Problem
{
    private string $fileName = '';
    private string $key = '';
    private int $weight = 1;
    private string $line = '';
    private int $lineNumber = 0;

    public function __construct(string $fileName, string $key, int $weight, string $line, int $lineNumber)
    {
        $this->fileName = $fileName;
        $this->key = $key;
        $this->weight = $weight;
        $this->line = $line;
        $this->lineNumber = $lineNumber;
    }

    public function lineNumber(): int
    {
        return $this->lineNumber;
    }

    public function format(): string
    {
        return sprintf(
            "<error>%s(%d)</error> in <info>%s</info>:<info>%d</info> \n    => %s\n",
            $this->key,
            $this->weight,
            $this->fileName,
            $this->lineNumber,
            $this->line
        );
    }
}

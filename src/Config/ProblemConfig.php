<?php

declare(strict_types=1);

namespace NotSoSimple\Config;

/** @psalm-immutable */
final class ProblemConfig implements ConfigInterface
{
    protected string $key;
    protected string $regex;
    protected int $weight;

    public function __construct(string $key, string $regex, int $weight)
    {
        $this->key = $key;
        $this->regex = $regex;
        $this->weight = $weight;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function regex(): string
    {
        return $this->regex;
    }

    public function weight(): int
    {
        return $this->weight;
    }

    /**
     * @psalm-mutation-free
     *
     * @return (int|string)[]
     *
     * @psalm-return array{key: string, regex: string, weight: int}
     */
    public function toArray(): array
    {
        return [
            'key'    => $this->key,
            'regex'  => $this->regex,
            'weight' => $this->weight,
        ];
    }

    public function scanLine(string $line): bool
    {
        if (preg_match($this->regex, $line)) {
            return true;
        }

        return false;
    }
}

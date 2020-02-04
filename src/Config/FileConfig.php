<?php declare(strict_types=1);

namespace NotSoSimple\Config;

/** @psalm-immutable */
final class FileConfig implements ConfigInterface
{
    protected string $path;
    protected bool $recursive;

    public function __construct(string $path, bool $recursive)
    {
        $this->path = $path;
        $this->recursive = $recursive;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function recursive(): bool
    {
        return $this->recursive;
    }

    /**
     * @psalm-mutation-free
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'path'      => $this->path,
            'recursive' => $this->recursive,
        ];
    }
}

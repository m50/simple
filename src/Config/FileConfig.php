<?php declare(strict_types=1);

namespace NotSoSimple\Config;

use NotSoSimple\DataObjects\Cwd;

/** @psalm-immutable */
final class FileConfig implements ConfigInterface
{
    protected string $path;
    protected bool $recursive;

    public function __construct(string $path, bool $recursive)
    {
        if (strpos($path, './') === 0) {
            $path = str_replace('./', Cwd::get() . DIRECTORY_SEPARATOR, $path);
        }
        if (strpos($path, '~/') === 0) {
            $path = str_replace('~/', (string)getenv('HOME') . DIRECTORY_SEPARATOR, $path);
        }
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

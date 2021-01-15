<?php

declare(strict_types=1);

namespace NotSoSimple\Config;

use NotSoSimple\DataObjects\Cwd;

/** @psalm-immutable */
final class FileConfig implements ConfigInterface
{
    protected string $path;
    protected bool $recursive;
    protected string $cwd;

    public function __construct(string $path, bool $recursive)
    {
        $this->cwd = Cwd::get();
        if (strpos($path, './') === 0) {
            $path = str_replace('./', $this->cwd . DIRECTORY_SEPARATOR, $path);
        }
        if (strpos($path, '~/') === 0) {
            $path = str_replace('~/', (string)getenv('HOME') . DIRECTORY_SEPARATOR, $path);
        }
        $this->path = $path;
        $this->recursive = $recursive;
    }

    public function path(): string
    {
        return str_replace($this->cwd, '.', $this->path);
    }

    public function recursive(): bool
    {
        return $this->recursive;
    }

    /**
     * @return array{path:string,recursive:bool}
     */
    public function toArray(): array
    {
        return [
            'path'      => $this->path(),
            'recursive' => $this->recursive,
        ];
    }
}

<?php declare(strict_types=1);

namespace NotSoSimple\Config;

use NotSoSimple\DataObjects\Cwd;

/** @psalm-immutable */
final class ExcludeConfig implements ConfigInterface
{
    protected string $path;
    protected bool $file;

    public function __construct(string $path, bool $file)
    {
        if (strpos($path, './') === 0) {
            $path = str_replace('./', Cwd::get() . DIRECTORY_SEPARATOR, $path);
        }
        if (strpos($path, '~/') === 0) {
            $path = str_replace('~/', (string)getenv('HOME') . DIRECTORY_SEPARATOR, $path);
        }
        $this->path = $path;
        $this->file = $file;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isFile(): bool
    {
        return $this->file;
    }

    /**
     * @psalm-mutation-free
     *
     * @return (bool|string)[]
     *
     * @psalm-return array{path: string, file: bool}
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'file' => $this->file,
        ];
    }
}

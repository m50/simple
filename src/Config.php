<?php

declare(strict_types=1);

namespace NotSoSimple;

use NotSoSimple\DataObjects\Cwd;
use Symfony\Component\Yaml\Yaml;
use NotSoSimple\Config\FileConfig;
use NotSoSimple\Config\ReportConfig;
use NotSoSimple\Config\ExcludeConfig;
use NotSoSimple\Config\ProblemConfig;
use NotSoSimple\Config\ConfigInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use NotSoSimple\Exceptions\UnableToLoadConfigException;

final class Config implements ConfigInterface
{
    public const VERSION = '1.0.0';

    private bool $shortcircuit = false;

    private ReportConfig $report;

    /** @var array<FileConfig> */
    private array $files = [];

    /** @var array<ExcludeConfig> */
    private array $exclusions = [];

    /** @var array<ProblemConfig> */
    private array $problems = [];

    /** @var array<string> */
    private array $extensions = [
        'md',
        'markdown',
        'html',
        'htm',
        'txt',
    ];

    public function __construct(string $file = './simple.yaml')
    {
        $this->report = new ReportConfig('', '');
        $this->problems = [
            new ProblemConfig('simple', '/simpl[ey]/i', 3),
            new ProblemConfig('easy', '/eas(?:y|ily)/i', 3),
            new ProblemConfig('quickly', '/quick(?:ly)?/i', 2),
            new ProblemConfig('real quick', '/real quick/i', 1),
            new ProblemConfig('todo', '/\btodo\b/i', 3),
        ];
        $this->files = [
            new FileConfig(Cwd::get() . DIRECTORY_SEPARATOR, true),
        ];
        $this->exclusions = [
            new ExcludeConfig('README.md', true),
        ];

        if (empty($file)) {
            return;
        }

        if (strpos($file, './') === 0) {
            $file = str_replace('./', Cwd::get() . DIRECTORY_SEPARATOR, $file);
        }

        try {
            /**
             * @var array{
             *      shortcircuit: ?bool,
             *      report: ?array<string,string>,
             *      extensions: ?array<string>,
             *      files: ?array<array{path:string,recursive:bool}>,
             *      problems: ?array<array{weight:int,key:string,regex:string}>,
             *      exclude: ?array<array{file:bool,pattern:string}>,
             *  }
             */
            $yaml = Yaml::parseFile($file);
            $this->parseConfig($yaml);
        } catch (ParseException $exception) {
            throw new UnableToLoadConfigException("Unable to parse file ({$file}). Does it exist?", 15, $exception);
        }
    }

    public static function generate(string $dir): int
    {
        $file = $dir . DIRECTORY_SEPARATOR . 'simple.yaml';

        $config = (new static(''))->toArray();
        $yaml = Yaml::dump($config, 4, 2);

        $yaml = str_replace("-\n    ", '- ', $yaml);

        $ret = file_put_contents($file, $yaml);

        if ($ret === false) {
            return 2;
        }

        if (file_exists($file)) {
            return 0;
        }

        return 1;
    }

    public function getReport(): ReportConfig
    {
        return $this->report;
    }

    /**
     * @return array<FileConfig>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array<string>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return array<ExcludeConfig>
     */
    public function getExclusions(): array
    {
        return $this->exclusions;
    }

    /**
     * @return array<ProblemConfig>
     */
    public function getProblems(): array
    {
        return $this->problems;
    }

    public function shortcircuit(): bool
    {
        return $this->shortcircuit;
    }

    /**
     * @psalm-mutation-free
     */
    public function toArray(): array
    {
        return [
            'shortcircuit'  => $this->shortcircuit,
            'report'        => $this->report->toArray(),
            'files'         => array_map(static function (FileConfig $file): array {
                return $file->toArray();
            }, $this->files),
            'exclude'         => array_map(static function (ExcludeConfig $file): array {
                return $file->toArray();
            }, $this->exclusions),
            'problems'      => array_map(static function (ProblemConfig $problem): array {
                return $problem->toArray();
            }, $this->problems),
            'extensions'    => $this->extensions,
        ];
    }

    /**
     * Parse a config file
     *
     * @param array{
     *      shortcircuit: ?bool,
     *      report: ?array<string,string>,
     *      extensions: ?array<string>,
     *      files: ?array<array{path:string, recursive:bool}>,
     *      problems: ?array<array{weight: int, key: string, regex: string}>,
     *      exclude: ?array<array{file: bool, path: string}>,
     *  } $config
     * @return void
     */
    private function parseConfig(array $config): void
    {
        if (isset($config['files'])) {
            $this->setFiles($config['files']);
        }
        if (isset($config['problems'])) {
            $this->setProblems($config['problems']);
        }
        if (isset($config['exclude'])) {
            $this->setExclusion($config['exclude']);
        }
        if (isset($config['shortcircuit'])) {
            $this->shortcircuit = $config['shortcircuit'];
        }
        if (isset($config['report'])) {
            $this->setReport($config['report']);
        }
        if (isset($config['extensions'])) {
            $this->extensions = $config['extensions'];
        }
    }

    /**
     * @param array<string,string> $value
     * @return void
     */
    private function setReport(array $value): void
    {
        /** @var string */
        $format = '';
        if (! isset($value['output'])) {
            throw new UnableToLoadConfigException('Report option set but no output file provided.', 18);
        }
        if (isset($value['format'])) {
            $format = $value['format'];
        }
        $this->report = new ReportConfig($value['output'], $format);
    }

    /**
     * @param array<array{weight:int,key:string,regex:string}> $values
     * @return void
     */
    public function setProblems(array $values): void
    {
        $this->problems = [];
        /** @var array<string,string|int> $value */
        foreach ($values as $value) {
            /** @var int */
            $weight = $value['weight'];
            /** @var string */
            $regex  = $value['regex'];
            /** @var string */
            $key    = $value['key'];
            $this->problems[] = new ProblemConfig($key, $regex, $weight);
        }
    }
    /**
     * @param array<array{path:string,recursive:bool}> $values
     * @return void
     */
    public function setFiles(array $values): void
    {
        $this->files = [];
        foreach ($values as $value) {
            /** @var string $path */
            $path = $value['path'];
            /** @var bool $recursive */
            $recursive = $value['recursive'];
            $this->files[] = new FileConfig($path, $recursive);
        }
    }
    /**
     * @param array<array{path:string,file:bool}> $values
     * @return void
     */
    public function setExclusion(array $values): void
    {
        $this->exclusions = [];
        foreach ($values as $value) {
            /** @var string $path */
            $path = $value['path'];
            /** @var bool $file */
            $file = $value['file'];
            $this->exclusions[] = new ExcludeConfig($path, $file);
        }
    }
}

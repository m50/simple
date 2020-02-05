<?php declare(strict_types=1);

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
            /** @var array<string,bool|array<string,string>|array<string>|array<array<string,string|int|bool>>> $yaml */
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

    public function getExtensions(): array
    {
        return $this->extensions;
    }

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
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'shortcircuit'  => $this->shortcircuit,
            'report'        => $this->report->toArray(),
            'files'         => array_map(static function (FileConfig $file): array {
                return $file->toArray();
            }, $this->files),
            'problems'      => array_map(static function (ProblemConfig $problem): array {
                return $problem->toArray();
            }, $this->problems),
            'extensions'    => $this->extensions,
        ];
    }

    /**
     * Parse a config file
     *
     * @param array<string,bool|array<string,string>|array<string>|array<array<string,string|int|bool>>> $config
     * @return void
     */
    private function parseConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            if ($key === 'shortcircuit') {
                /** @var bool $value */
                $this->shortcircuit = $value;
            } elseif ($key === 'report') {
                /** @var array<string,string> $value */
                $value = $value; // This is needed to make psalm happy.
                $this->setReport($value);
            } elseif ($key === 'extensions') {
                /** @var array<string> $value */
                $this->extensions = $value;
            } elseif ($key === 'files') {
                /** @var array<array<string,string|bool>> $value */
                $value = $value; // This is needed to make psalm happy.
                $this->setFiles($value);
            } elseif ($key === 'problems') {
                /** @var array<array<string,string|bool>> $value */
                $value = $value; // This is needed to make psalm happy.
                $this->setProblems($value);
            }
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
     * @param array<array<string,string|int>> $values
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
     * @param array<array<string,string|bool>> $values
     * @return void
     */
    public function setFiles(array $values): void
    {
        $this->files = [];
        /** @var array<string,string|bool> $value */
        foreach ($values as $value) {
            /** @var string */
            $path = $value['path'];
            /** @var bool */
            $recursive = $value['recursive'];
            $this->files[] = new FileConfig($path, $recursive);
        }
    }
}

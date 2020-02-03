<?php declare(strict_types=1);

namespace NotSoSimple;

use NotSoSimple\Exceptions\UnableToLoadConfigException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    private bool $recursive = true;
    /** @var array<string,string> $problemRegexs */
    private array $problemRegexes = [
        'simple'     => '/simpl[ey]/i',
        'easy'       => '/eas(?:y|ily)/i',
        'quickly'    => '/quickly/i',
        'real quick' => '/real quick/i',
        'todo'       => '/\btodo\b/i',
    ];

    public function __construct(string $file = 'simple.yaml')
    {
        try {
            /** @var array<string,mixed> $yaml */
            $yaml = Yaml::parseFile($file);
            $this->parseConfig($yaml);
        } catch (ParseException $exception) {
            // We don't really care if the config file doesn't exist, we will
            //  use our default config in that case.
        }
    }

    public static function generate(string $dir): int
    {
        $yaml = Yaml::dump((new static)->toArray());
        $ret = file_put_contents($dir . PATH_SEPARATOR . 'simple.yaml', $yaml);

        if ($ret === false) {
            return 1;
        }

        return 0;
    }

    public function toArray(): array
    {
        $methods = get_class_methods($this);
        $arr = [];
        foreach ($methods as $method) {
            if (preg_match('/^get([A-Z]\w+)$/', $method, $matches)) {
                $arr[lcfirst($matches[1])] = $this->$method();
            }
        }

        return $arr;
    }

    public function getRecursive(): bool
    {
        return $this->recursive;
    }

    public function getProblemRegularExpressions(): array
    {
        return $this->problemRegexes;
    }

    private function setRecursive(bool $recursive): void
    {
        $this->recursive = $recursive;
    }

    /**
     * @param array<string,string> $problemRegexes
     * @return void
     */
    private function setProblemRegularExpressions(array $problemRegexes): void
    {
        foreach ($problemRegexes as $key => $value) {
            if (! is_string($key)) {
                throw new UnableToLoadConfigException('Invalid key for problem regular expressions', 33);
            }
            if (! is_string($value) || ! preg_match('/^\/.*\/[is]$/', $value)) {
                throw new UnableToLoadConfigException('Invalid regular expression for problem regular expressions', 34);
            }
        }
        $this->problemRegexes = $problemRegexes;
    }

    /**
     * Parse a config file
     *
     * @param array<string,mixed> $config
     * @return void
     */
    private function parseConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }
}

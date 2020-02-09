<?php

declare(strict_types=1);

namespace NotSoSimple\Commands;

use Exception;
use NotSoSimple\Config;
use NotSoSimple\Config\ExcludeConfig;
use NotSoSimple\Writer;
use NotSoSimple\FileReader;
use NotSoSimple\DataObjects\Cwd;
use NotSoSimple\Config\FileConfig;
use NotSoSimple\Reports\HtmlReport;
use NotSoSimple\Reports\JsonReport;
use NotSoSimple\Config\ReportConfig;
use NotSoSimple\DataObjects\Problem;
use NotSoSimple\Reports\JUnitReport;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use NotSoSimple\Exceptions\UnableToLoadConfigException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class ScanCommand extends SymfonyCommand
{
    private static float $START_TIME = 0;
    protected static $defaultName = 'scan';

    protected ?InputInterface $input = null;

    protected Config $config;

    private const OPTIONS = [
        [
            'files',
            'f',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The files or directories that are to be (recursively) scanned.',
        ],
        [
            'exclude',
            'e',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The directories that are to be excluded from scanning.',
        ],
        ['ext', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The extension(s) to be scanned.'],
        ['no-color', null, InputOption::VALUE_NONE, 'Disable color output.'],
        ['no-progress-bar', null, InputOption::VALUE_NONE, 'Disable progress bar.'],
        ['quiet', 'q', InputOption::VALUE_NONE, 'Don\'t output anything to console.'],
        [
            'hide-results',
            null,
            InputOption::VALUE_NONE,
            'Hide the results from the console, relying on the reports instead'
        ],
        [
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The config file to use. Defaults to "simple.yaml" in the current working directory.',
        ],
        ['report-format', null, InputOption::VALUE_REQUIRED, 'The format for the report (json, junit, html).'],
        [
            'report-file',
            null,
            InputOption::VALUE_REQUIRED,
            'The file to output the report to (Note: Formatting can be implied by extension).',
        ],
        ['ignore-weight', 'i', InputOption::VALUE_REQUIRED, 'The weight equal to or less than to ignore.'],
    ];

    public function __construct(?string $name = null)
    {
        self::$START_TIME = microtime(true);
        $this->config = new Config('');
        parent::__construct($name);
    }

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Scan files for use of poor documentation language.')
            ->setHelp($this->genHelp());

        /** @var array{string,string,int,string} $option */
        foreach (self::OPTIONS as $option) {
            [$name, $shortcut, $mode, $description] = $option;
            $this->addOption($name, $shortcut, $mode, $description);
        }
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Writer::$output = $output;
        $this->handleConfig($input);

        $files = $this->getFiles();

        $errors = [];
        foreach ($files as $file) {
            Writer::comment("Scanning <info>{$file->path()}</info>...");
            $errors = array_merge($errors, $this->scan($file));
        }

        Writer::writeln("\n");

        if (! $input->getOption('hide-results')) {
            $this->writeErrors($errors);
        }

        $this->genReport($errors, $input);

        $this->outputTime();

        return count($errors) > 0 ? 1 : 0;
    }

    /**
     * @return array<Problem>
     */
    private function scan(FileConfig $file): array
    {
        $problems = [];

        $finder = $this->getFinder($file);

        Writer::pbStart(count($finder));

        foreach ($finder as $file) {
            Writer::pbAdvance();
            if (count($problems) > 0 && $this->config->shortcircuit()) {
                continue;
            }

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $contents = $file->getContents();
            $lines = explode("\n", $contents);

            $freader = new FileReader($file->getPathname(), $lines, $this->config);
            $problems = array_merge($problems, $freader->read()->getErrors());
        }

        Writer::pbFinish();

        return $problems;
    }

    private function getFinder(FileConfig $file): Finder
    {
        $extensions = array_map(static function (string $ext): string {
            return '*.' . $ext;
        }, $this->config->getExtensions());

        $finder = new Finder();
        $finder->ignoreUnreadableDirs()->files();

        if (! $file->recursive()) {
            $finder->depth(0);
        }

        $this->handleExclusions($finder);

        if (is_dir($file->path())) {
            $finder->name($extensions)->in($file->path());
        } else {
            $finder->name(basename($file->path()))->in(dirname($file->path()))->depth(0);
        }

        return $finder;
    }

    private function handleExclusions(Finder &$finder): void
    {
        $exclusions = $this->getExclusions();

        $excludeFiles = array_map(static function (ExcludeConfig $config): string {
            return $config->path();
        }, array_filter($exclusions, static function (ExcludeConfig $config): bool {
            return $config->isFile();
        }));

        $excludePaths = array_map(static function (ExcludeConfig $config): string {
            return $config->path();
        }, array_filter($exclusions, static function (ExcludeConfig $config): bool {
            return ! $config->isFile();
        }));

        if (! empty($excludePaths)) {
            $finder->exclude($excludePaths);
        }

        if (! empty($excludeFiles)) {
            $finder->notName($excludeFiles);
        }
    }

    private function handleConfig(InputInterface $input): void
    {
        Writer::$quiet         = (bool) ($input->getOption('quiet')           ?? false);
        Writer::$noColor       = (bool) ($input->getOption('no-color')        ?? false);
        Writer::$noProgressBar = (bool) ($input->getOption('no-progress-bar') ?? false);

        $this->config = new Config($this->getConfigFile($input));
        $this->input = $input;
    }

    /**
     * @param array<Problem> $errors
     */
    private function genReport(array $errors, InputInterface $input): void
    {
        $report = $this->config->getReport();
        /** @var string|null $reportFile */
        $reportFile = $input->getOption('report-file') ?? $report->output();
        if (is_null($reportFile)) {
            return;
        }
        $reportFormat = $this->getReportFormat($input, $reportFile) ?? $report->format();

        switch (strtolower($reportFormat)) {
            case 'json':
                JsonReport::generate($reportFile, $errors);
                break;
            case 'junit':
                JUnitReport::generate($reportFile, $errors);
                break;
            case 'html':
                HtmlReport::generate($reportFile, $errors);
        }
    }

    /** @param array<Problem> $errors */
    private function writeErrors(array $errors): void
    {
        foreach ($errors as $error) {
            Writer::writeln($error->format());
        }
    }

    private function outputTime(): void
    {
        $time = (float) (microtime(true) - self::$START_TIME);
        Writer::writeln(sprintf('Simple took <info>%0.2f seconds</info> to run.', $time));
    }

    private function getReportFormat(InputInterface $input, string $reportFile): ?string
    {
        try {
            /** @var string|null $reportFormat */
            $reportFormat = $input->getOption('report-format');

            if (is_null($reportFormat)) {
                $reportFormat = ReportConfig::getReportFormat($reportFile);
            }

            $reportFormat = strtolower($reportFormat);
        } catch (Exception $e) {
            return null;
        }

        return $reportFormat;
    }

    /**
     * @return array<FileConfig>
     *
     */
    private function getFiles(): array
    {
        $files = [];
        if (! is_null($this->input)) {
            $files = $this->input->getOption('files');
        }

        if (empty($files)) {
            $files = $this->config->getFiles();
        } else {
            if (is_string($files)) {
                return [new FileConfig($files, true)];
            }
            if (is_bool($files)) {
                throw new UnableToLoadConfigException('Unable to determine directory.', 10);
            }
            $files = array_map(static function (string $file): FileConfig {
                return new FileConfig($file, true);
            }, $files);
        }

        return $files;
    }

    private function getExclusions(): array
    {
        $files = [];
        if (! is_null($this->input)) {
            $files = $this->input->getOption('exclude');
        }

        if (empty($files)) {
            $files = $this->config->getExclusions();
        } else {
            if (is_string($files)) {
                return [new ExcludeConfig($files, (bool)preg_match('/\.\w+$/', $files))];
            }
            if (is_bool($files)) {
                throw new UnableToLoadConfigException('Unable to determine directory.', 10);
            }
            $files = array_map(static function (string $file): ExcludeConfig {
                return new ExcludeConfig($file, (bool)preg_match('/\.\w+$/', $file));
            }, $files);
        }

        return $files;
    }

    /**
     * Get the config file path.
     *
     * @param InputInterface $input
     * @return string
     */
    private function getConfigFile(InputInterface $input): string
    {
        /** @var string|null $configFile */
        $configFile = $input->getOption('config');
        if (is_null($configFile)) {
            $configFile = Cwd::get() . DIRECTORY_SEPARATOR . 'simple.yaml';
        }

        if (! file_exists($configFile)) {
            $configFile = '';
        }

        return $configFile;
    }

    private function genHelp(): string
    {
        return 'Run <info>Simple</info> in your CI process on your documentation to make ' .
            'sure you don\'t put out any documentation that is condescending or ' .
            "unhelpful to learners.\nEverywhere that <info>Simple</info> finds any of the " .
            'problematic words, it may be a perfect case to provide more detailed documentation.';
    }
}

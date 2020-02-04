<?php declare(strict_types=1);

namespace NotSoSimple;

use Exception;
use NotSoSimple\Config\ReportConfig;
use NotSoSimple\DataObjects\Cwd;
use NotSoSimple\Exceptions\UnableToLoadConfigException;
use NotSoSimple\Reports\HtmlReport;
use NotSoSimple\Reports\JsonReport;
use NotSoSimple\Reports\JUnitReport;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ScanCommand extends SymfonyCommand
{
    protected static $defaultName = 'scan';

    protected Config $config;

    private const OPTIONS = [
        [
            'files',
            'f',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The files or directories that are to be scanned.'
        ],
        ['ext', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The extension(s) to be scanned.'],
        ['no-color', 'n', InputOption::VALUE_NONE, 'Disable color output.'],
        [
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The config file to use. Defaults to "simple.yaml" in the current working directory.'
        ],
        [
            'gen-config',
            null,
            InputOption::VALUE_NONE,
            'Generate a new config as "simple.yaml" in the current working directory.'
        ],
        ['report-format', null, InputOption::VALUE_REQUIRED, 'The format for the report (json, junit, html).'],
        [
            'report-file',
            null,
            InputOption::VALUE_REQUIRED,
            'The file to output the report to (Note: Formatting can be implied by extension).'
        ],
    ];

    public function __construct(?string $name = null)
    {
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workingDirs = $this->getWorkingDirs($input);

        /** @var bool|null $genConfig */
        $genConfig = $input->getOption('gen-config');
        if (isset($genConfig) && $genConfig) {
            return Config::generate($workingDirs[0]);
        }

        $this->config = new Config($this->getConfigFile($input));

        $errors = [];
        foreach ($workingDirs as $dir) {
            $errors = array_merge($errors, $this->scanDir($dir));
        }

        $this->genReport($errors, $input);

        return count($errors) > 0 ? 1 : 0;
    }

    private function scanDir(string $dir): array
    {
        return [];
    }

    private function genReport(array $errors, InputInterface $input): void
    {
        $report = $this->config->getReport();
        /** @var string|null $reportFile */
        $reportFile = $input->getOption('report-file') ?? $report->output();
        if (is_null($reportFile)) {
            return;
        }
        $reportFormat = $this->getReportFormat($input, $reportFile) ?? $report->format();

        switch ($reportFormat) {
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
     * @return string[]
     */
    private function getWorkingDirs(InputInterface $input): array
    {
        $workingDirs = $input->getOption('dir');
        if (is_null($workingDirs)) {
            $cwd = getcwd();
            if ($cwd === false) {
                throw new UnableToLoadConfigException('Unable to determine directory.', 10);
            }
            $workingDirs = [$cwd];
        }
        if (is_string($workingDirs)) {
            return [$workingDirs];
        }
        if (is_bool($workingDirs)) {
            throw new UnableToLoadConfigException('Unable to determine directory.', 10);
        }

        return $workingDirs;
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
            $configFile = Cwd::get() . PATH_SEPARATOR . 'simple.yaml';
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

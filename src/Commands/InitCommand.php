<?php declare(strict_types=1);

namespace NotSoSimple\Commands;

use NotSoSimple\Config;
use NotSoSimple\Writer;
use NotSoSimple\DataObjects\Cwd;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class InitCommand extends Command
{
    protected static $defaultName = 'init';

    private const OPTIONS = [
        ['no-color', null, InputOption::VALUE_NONE, 'Disable color output.'],
        ['no-progress-bar', null, InputOption::VALUE_NONE, 'Disable progress bar.'],
        ['quiet', 'q', InputOption::VALUE_NONE, 'Quiet mode.'],
    ];

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate the documentation file for <info>Simple</info>.')
            ->setHelp('');

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
        Writer::$output = $output;
        $this->handleConfig($input);

        $response = Config::generate(Cwd::get());

        if ($response === 0) {
            Writer::writeln('<info>Successfully generated <comment>simple.yaml</comment></info>');
        } else {
            Writer::writeln('<error>Failed to write <comment>simple.yaml</comment></error>');
        }

        return $response;
    }

    private function handleConfig(InputInterface $input): void
    {
        Writer::$quiet         = (bool) ($input->getOption('quiet')           ?? false);
        Writer::$noColor       = (bool) ($input->getOption('no-color')        ?? false);
        Writer::$noProgressBar = (bool) ($input->getOption('no-progress-bar') ?? false);
    }
}

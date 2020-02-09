<?php

namespace App\Tests\Command;

use PHPUnit\Framework\TestCase;
use NotSoSimple\Commands\InitCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\TesterTrait;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends TestCase
{
    use TesterTrait;

    public function testExecute()
    {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertRegexp('/Successfully generated simple\.yaml/', $output);
    }
}

<?php

namespace NotSoSimple\Tests\Integration;

use PHPUnit\Framework\TestCase;
use NotSoSimple\Commands\InitCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\TesterTrait;
use Symfony\Component\Console\Tester\CommandTester;

class InitTest extends TestCase
{
    use TesterTrait;

    /** @test */
    function test_execute()
    {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);
        $this->assertEquals(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully generated simple.yaml', $output);
    }
}

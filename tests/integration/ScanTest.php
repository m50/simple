<?php

namespace App\Tests\Command;

use PHPUnit\Framework\TestCase;
use NotSoSimple\Commands\InitCommand;
use NotSoSimple\Commands\ScanCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\TesterTrait;
use Symfony\Component\Console\Tester\CommandTester;

class ScanTest extends TestCase
{
    use TesterTrait;

    private static ?CommandTester $commandTester = null;

    private static string $outputDir = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $application = new Application();
        $application->add(new ScanCommand());

        $command = $application->find('scan');
        static::$commandTester = new CommandTester($command);

        static::$outputDir = getenv('TEST_OUTPUT_DIR') ?? __DIR__;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // @unlink(static::$outputDir . '/report.json');
        // @unlink(static::$outputDir . '/report.html');
        // @unlink(static::$outputDir . '/report.junit.xml');
    }

    /** @test */
    function test_execute()
    {
        static::$commandTester->execute(['--files' => __DIR__ . '/../../README.md', '-e' => './vendor/']);
        $output = static::$commandTester->getDisplay();
        $this->assertRegExp('/Scanning [\.\/a-z]+README.md.../', $output);
        $this->assertRegExp('/Simple took \d\.\d+ seconds to run\./', $output);
        $this->assertRegExp('/simple\(\d\)\s*in\s*[\.\/a-z]+README\.md:\d+\s*\n\s+\=\>/', $output);
    }

    /** @test */
    function test_execute_json_report()
    {
        static::$commandTester->execute([
            '--files' => __DIR__ . '/../../README.md',
            '-e' => './vendor/',
            '--report-file' => static::$outputDir . '/report.json',
        ]);

        $this->assertFileExists(static::$outputDir . '/report.json');
    }

    /** @test */
    function test_execute_html_report()
    {
        static::$commandTester->execute([
            '--files' => __DIR__ . '/../../README.md',
            '-e' => './vendor/',
            '--report-file' => static::$outputDir . '/report.html',
        ]);

        $this->assertFileExists(static::$outputDir . '/report.html');
    }

    /** @test */
    function test_execute_junit_report()
    {
        static::$commandTester->execute([
            '--files' => __DIR__ . '/../../README.md',
            '-e' => './vendor/',
            '--report-file' => static::$outputDir . '/report.junit.xml',
        ]);

        $this->assertFileExists(static::$outputDir . '/report.junit.xml');
    }
}

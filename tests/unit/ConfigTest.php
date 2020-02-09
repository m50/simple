<?php

namespace NotSoSimple\Tests\Unit;

use NotSoSimple\Config;
use NotSoSimple\Config\ExcludeConfig;
use NotSoSimple\Config\FileConfig;
use NotSoSimple\Config\ProblemConfig;
use NotSoSimple\Config\ReportConfig;
use NotSoSimple\Exceptions\UnableToLoadConfigException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private static string $configPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$configPath = __DIR__ . '/../../simple.example.yaml';
    }

    /** @test */
    function it_can_read_a_configuration_file()
    {
        $config = new Config(static::$configPath);

        $array = $config->toArray();

        $this->assertArrayHasKey('shortcircuit', $array);
        $this->assertArrayHasKey('report', $array);
        $this->assertArrayHasKey('files', $array);
        $this->assertArrayHasKey('exclude', $array);
        $this->assertArrayHasKey('problems', $array);
        $this->assertArrayHasKey('extensions', $array);

        $this->assertTrue($config->getReport() instanceof ReportConfig);

        $this->assertIsArray($config->getProblems());
        $this->assertTrue($config->getProblems()[0] instanceof ProblemConfig);

        $this->assertIsArray($config->getExclusions());
        $this->assertTrue($config->getExclusions()[0] instanceof ExcludeConfig);

        $this->assertIsString($config->getExtensions()[0]);
        $this->assertIsArray($config->getExtensions());

        $this->assertIsArray($config->getFiles());
        $this->assertTrue($config->getFiles()[0] instanceof FileConfig);
    }

    /** @test */
    function test_report_config()
    {
        $rconfig = new ReportConfig('report.json');

        $this->assertEquals('json', $rconfig->format());
        $this->assertEquals('report.json', $rconfig->output());

        $this->assertEquals([
            'format' => 'json',
            'output' => 'report.json',
        ], $rconfig->toArray());

        $this->expectException(UnableToLoadConfigException::class);
        new ReportConfig('report');
    }

    /** @test */
    function test_problem_config()
    {
        $pconfig = new ProblemConfig('simple', '/simple/i', 3);

        $this->assertEquals('simple', $pconfig->key());
        $this->assertEquals('/simple/i', $pconfig->regex());
        $this->assertEquals(3, $pconfig->weight());

        $this->assertTrue($pconfig->scanLine('This contains simple.'));
        $this->assertFalse($pconfig->scanLine('This contains nothing.'));
    }

    /** @test */
    function test_file_config()
    {
        $fconfig = new FileConfig('~/docs', true);

        $this->assertTrue($fconfig->recursive());

        $this->assertEquals((string)getenv('HOME') . DIRECTORY_SEPARATOR . 'docs', $fconfig->path());
    }

    /** @test */
    function test_exclude_config()
    {
        $econfig = new ExcludeConfig('~/vendor', false);
        $this->assertEquals((string)getenv('HOME') . DIRECTORY_SEPARATOR . 'vendor', $econfig->path());

        $econfig = new ExcludeConfig('./vendor', false);
        $this->assertEquals((string)getcwd() . DIRECTORY_SEPARATOR . 'vendor', $econfig->path());

        $econfig = new ExcludeConfig('README.md', true);
        $this->assertTrue($econfig->isFile());
        $this->assertEquals('README.md', $econfig->path());
    }
}

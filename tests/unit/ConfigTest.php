<?php

namespace NotSoSimple\Tests\Unit;

use NotSoSimple\Config;
use NotSoSimple\Config\ReportConfig;
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
    public function it_can_read_a_configuration_file()
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
        $this->assertIsArray($config->getExclusions());
        $this->assertIsArray($config->getExtensions());
        $this->assertIsArray($config->getProblems());
        $this->assertIsArray($config->getFiles());
    }
}

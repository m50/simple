<?php

declare(strict_types=1);

namespace NotSoSimple\Reports;

use DOMDocument;
use DOMElement;
use NotSoSimple\DataObjects\Problem;

final class JUnitReport extends Report
{
    private const SCHEMA = 'https://raw.githubusercontent.com/junit-team/' .
        'junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd';
    private const XMLNS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * Generate an HTML report.
     *
     * @param string $file
     * @param array<\NotSoSimple\DataObjects\Problem> $problems
     * @return void
     *
     * @psalm-param list<\NotSoSimple\DataObjects\Problem> $problems
     */
    public function generate(string $file, array $problems): void
    {
        file_put_contents($file, $this->generateDom($problems)->saveXML());
    }

    /**
     * @psalm-param list<\NotSoSimple\DataObjects\Problem> $problems
     */
    private function generateDom(array $problems): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        /** @var array<string,array{errors:array,warnings:array}> $tests */
        $tests = $this->problemsToTests($problems);

        $suite = $this->generateTestsuite($dom, $tests);

        $suites = $dom->createElement('testsuites');
        $suites->appendChild($suite);
        $dom->appendChild($suites);

        if (count($problems) === 0) {
            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('name', 'simple');
            $suite->appendChild($testcase);
        }

        foreach ($tests as $fname => $opt) {
            $errorsCt = count($opt['errors']);
            $warningsCt = count($opt['warnings']);

            $testsuite = $dom->createElement('testsuite');
            $testsuite->setAttribute('name', $fname);
            $testsuite->setAttribute('file', $fname);
            $testsuite->setAttribute('assertions', (string) ($errorsCt + $warningsCt));
            $testsuite->setAttribute('failures', (string) $errorsCt);
            $testsuite->setAttribute('warnings', (string) $warningsCt);
            // $testsuite->setAttribute('tests', (string) count($failuresByType));

            foreach (['errors', 'warnings'] as $type) {
                /** @var array $error */
                foreach ($opt[$type] as $error) {
                    $testcase = $this->generateTestcase($dom, $fname, $error);
                    $testsuite->appendChild($testcase);
                }
            }

            $suite->appendChild($testsuite);
        }

        return $dom;
    }

    private function generateTestcase(DOMDocument $dom, string $fileName, array $error): DOMElement
    {
        $testcase = $dom->createElement('testcase');
        $testcase->setAttribute('name', "{$fileName}:{$error['line_number']}");
        $testcase->setAttribute('file', $fileName);
        $testcase->setAttribute('class', (string)$error['key']);
        $testcase->setAttribute('classname', (string)$error['key']);
        $testcase->setAttribute('line', (string) $error['line_number']);
        $testcase->setAttribute('assertions', '1');

        $failure = $dom->createElement('failure');
        $failure->setAttribute('type', $error['weight'] < 3 ? 'WARNING' : 'ERROR');
        $failure->nodeValue = trim(htmlentities((string) $error['line']));

        $testcase->appendChild($failure);

        return $testcase;
    }

    /**
     * Generate the test suite element.
     *
     * @param DOMDocument $dom
     * @param array $tests
     * @psalm-param array<string,array{errors:array,warnings:array}> $tests
     * @return DOMElement
     */
    private static function generateTestsuite(DOMDocument $dom, array $tests): DOMElement
    {
        $tErrorsCt = 0;
        $tWarningsCt = 0;
        foreach ($tests as $opt) {
            $tErrorsCt += count($opt['errors']);
            $tWarningsCt += count($opt['warnings']);
        }

        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', 'simple');
        $testsuite->setAttribute('tests', (string) count($tests));
        $testsuite->setAttribute('failures', (string) $tErrorsCt);
        $testsuite->setAttribute('warnings', (string) $tWarningsCt);
        $testsuite->setAttribute('xmlns:xsi', static::XMLNS_XSI);
        $testsuite->setAttribute('xsi:noNamespaceSchemaLocation', static::SCHEMA);

        return $testsuite;
    }

    /**
     * @psalm-param array<\NotSoSimple\DataObjects\Problem> $errors
     *
     * @psalm-return array<string, array<string, list<array>>>
     * @return array[][][]
     */
    private function problemsToTests(array $errors): array
    {
        $tests = [];

        /** @var Problem $error */
        foreach ($errors as $error) {
            if (empty($tests[$error->fileName()])) {
                $tests[$error->fileName()] = [
                    'warnings' => [],
                    'errors'   => [],
                ];
            }
            $type = $error->weight() < 3 ? 'warnings' : 'errors';
            $tests[$error->fileName()][$type][] = $this->generateEntry($error);
        }

        return $tests;
    }

    private function generateEntry(Problem $error): array
    {
        return [
            'key' => $error->key(),
            'line' => $error->unformattedLine(),
            'weight' => $error->weight(),
            'line_number' => $error->lineNumber(),
        ];
    }
}

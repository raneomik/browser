<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\LegacyExtension;

final class NormalizationTest extends TestCase
{
    public static function namesProvider(): \Generator
    {
        $baseTemplate = 'error_' . __METHOD__;
        $stringTransform = ['from' => '\\:', 'to' => '-_'];

        yield 'test name without datasets' => [
            'test name' => __METHOD__,
            'expected output' => \strtr($baseTemplate, ...$stringTransform) . '__0',
        ];

        $datasetTemplate = $baseTemplate . '__data-set-%s__0';

        $alphaTemplate = sprintf($datasetTemplate, 'test-set');
        $alphaOutput = \strtr($alphaTemplate, ...$stringTransform);

        $numericTemplate = sprintf($datasetTemplate, '0');
        $numericOutput = \strtr($numericTemplate, ...$stringTransform);

        $datasetMessagePrefix = __METHOD__ . ' with data set ';

        yield 'phpunit 10 alpha' => [$datasetMessagePrefix . '"test set"', $alphaOutput];
        yield 'phpunit 10 numeric' => [$datasetMessagePrefix . '#0', $numericOutput];
        yield 'legacy alpha' => [$datasetMessagePrefix . '"test set" (test set)', $alphaOutput];
        yield 'legacy numeric' => [$datasetMessagePrefix . '#0 (test set)', $numericOutput];

        foreach (self::edgeCaseTestNames() as $caseIndex => $edgeCaseName) {
            $alphaTemplate = sprintf($datasetTemplate, $edgeCaseName);
            $alphaOutput = \strtr($alphaTemplate, ...$stringTransform);

            yield $caseIndex => [$datasetMessagePrefix . $edgeCaseName, $alphaOutput];
        }
    }

    private static function edgeCaseTestNames(): \Generator
    {
        yield 'self within moustache' => 'te{{self}}st';
        yield 'double quoted with space' => '_self.env.setCache("uri://host.net:2121") _self.env.loadTemplate("other-host")';
        yield 'double quotes in moustache' => 'te{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}st';
        yield 'escaped simple quote' => 'te{{\'/etc/passwd\'|file_excerpt(1,30)}}st';
        yield 'single quote for array index access' => 'te{{[\'id\']|filter(\'system\')}}st';
        yield 'numeric array access' => 'te{{[0]|reduce(\'system\',\'id\')}}st';
    }

    /**
     * @test
     * @dataProvider namesProvider
     */
    public function can_normalize_test_names(string $testName, string $normalizedName): void
    {
        $browser = $this->createMock(Browser::class);
        $browser
            ->expects(self::once())
            ->method('saveCurrentState')
            ->with($normalizedName);

        $extension = new LegacyExtension();
        $extension->executeBeforeFirstTest();
        $extension->executeBeforeTest($testName);
        $extension::registerBrowser($browser);
        $extension->executeAfterTestError($testName, '', 0);
    }
}

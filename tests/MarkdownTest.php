<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use xenocrat\markdown\Markdown;

final class MarkdownTest extends TestCase
{
    public static function provideTestCases() : iterable
    {
        return parent::getTestCases('Markdown');
    }

    /**
     * @param string $source
     * @param string $expected
     */
    #[DataProvider('provideTestCases')]
    public function test(string $source, string $expected) : void
    {
        $instance = new Markdown();
        $instance->html5 = false;

        self::assertSame($instance->parse($source), $expected);
    }
}

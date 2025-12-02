<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use xenocrat\markdown\ChyrpMarkdown;

final class ChyrpMarkdownTest extends TestCase
{
    public static function provideTestCases() : iterable
    {
        return parent::getTestCases('ChyrpMarkdown');
    }

    /**
     * @param string $source
     * @param string $expected
     */
    #[DataProvider('provideTestCases')]
    public function test(string $source, string $expected) : void
    {
        $instance = new ChyrpMarkdown();

        $html = $instance->parse($source);

        self::assertTrue(mb_check_encoding($html, "UTF-8"));
        self::assertSame($html, $expected);
    }
}

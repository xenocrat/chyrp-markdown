<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use xenocrat\markdown\GithubMarkdown;

final class GithubMarkdownTest extends TestCase
{
    public static function provideTestCases() : iterable
    {
        return parent::getTestCases('GithubMarkdown');
    }

    /**
     * @param string $source
     * @param string $expected
     */
    #[DataProvider('provideTestCases')]
    public function test(string $source, string $expected) : void
    {
        $instance = new GithubMarkdown();
        $instance->html5 = true;
        $instance->renderCheckboxInputs = true;

        self::assertSame($instance->parse($source), $expected);
    }
}

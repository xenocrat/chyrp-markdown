<?php

declare(strict_types=1);

namespace Tests;

use DirectoryIterator;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

class TestCase extends BaseTestCase
{
    /**
     * Get test cases
     *
     * @param  string $parser
     *
     * @return iterable
     */
    public static function getTestCases(string $parser) : iterable
    {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $parser;

        $tests = new DirectoryIterator($dir);

        $data = [];

        foreach ($tests as $test) {
            if (!$test->isFile()) {
                continue;
            }

            $path = $test->getPathname();
            $extension = $test->getExtension();
            $basename = $test->getBasename('.' . $extension);

            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException("read file {$path}");
            }

            if (!mb_check_encoding($contents, "UTF-8")) {
                throw new RuntimeException("{$parser} test {$basename} source is invalid UTF-8.");
            }

            switch ($extension) {
                case 'md':
                    $data[$basename]['source'] = $contents;
                    $data[$basename]['test'] = $basename;
                    break;

                case 'html':
                    $data[$basename]['expected'] = $contents;
                    break;

                default:
                    throw new RuntimeException("unsupported extension {$extension}");
            }
        }

        return $data;
    }
}

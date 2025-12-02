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
     * @param  string $dir
     *
     * @return iterable
     */
    public static function getTestCases(string $dir) : iterable
    {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $dir;

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

            switch ($extension) {
                case 'md':
                    $data[$basename]['source'] = $contents;
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

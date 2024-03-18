<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt, 2024 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace cebe\markdown\block;

/**
 * Adds fenced code blocks.
 *
 * Automatically includes indented code block support.
 */
trait FencedCodeTrait
{
	use CodeTrait;

	/**
	 * Identify a line as the beginning of a fenced code block.
	 */
	protected function identifyFencedCode($line): bool
	{
		return (
			preg_match('/^~{3,}/', $line)
			|| preg_match('/^`{3,}[^`]*$/', $line)
		);
	}

	/**
	 * Consume lines for a fenced code block.
	 */
	protected function consumeFencedCode($lines, $current): array
	{
		$line = ltrim($lines[$current]);
		$fence = substr($line, 0, $pos = strspn($line, $line[0]));
		$language = trim(substr($line, $pos));
		$content = [];

		// consume until end fence
		for ($i = $current + 1, $count = count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			if (
				($pos = strspn($line, $fence[0])) < strlen($fence)
				|| ltrim(substr($line, $pos)) !== ''
			) {
				$content[] = $line;
			} else {
				break;
			}
		}
		$block = [
			'code',
			'content' => implode("\n", $content),
		];
		if ($language !== '') {
			if (preg_match('/^[^ ]+/', $language, $match)) {
				$block['language'] = $this->unEscapeBackslash($match[0]);
			}
		}

		return [$block, $i];
	}
}

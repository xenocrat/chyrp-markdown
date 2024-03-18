<?php
/**
 * @copyright Copyright (c) 2023 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace cebe\markdown\block;

/**
 * Adds the figure and figcaption elements
 */
trait FigureTrait
{
	/**
	 * identify a line as the beginning of a figure.
	 */
	protected function identifyFigure($line): bool
	{
		return (
			$line[0] === ':' and !isset($line[1])
			|| str_starts_with($line, ': ')
			|| str_starts_with($line, ':: ')
		);
	}

	/**
	 * Consume lines for a figure element
	 */
	protected function consumeFigure($lines, $current): array
	{
		$content = [];
		$caption = [];

		// consume until end of markers
		for ($i = $current, $count = count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			if (ltrim($line) !== '') {
				if ($line[0] == ':' && !isset($line[1])) {
					$line = '';
				} elseif (str_starts_with($line, ': ')) {
					$line = substr($line, 2);
				} elseif (str_starts_with($line, ':: ')) {
					$caption[$i] = substr($line, 3);
					continue;
				} else {
					--$i;
					break;
				}
				$content[] = $line;
			} else {
				break;
			}
		}

		// decide caption placement and remove invalid lines.
		if (isset($caption[$current])) {
			$endcap = false;
			for ($x = $current; $x < $i; $x++) { 
				if ($x !== $current && !isset($caption[$x - 1])) {
					unset($caption[$x]);
				}
			}
		} elseif (isset($caption[$i - 1])) {
			$endcap = true;
			for ($x = $i - 1; $x >= $current; $x--) { 
				if ($x !== $i - 1 && !isset($caption[$x + 1])) {
					unset($caption[$x]);
				}
			}
		} else {
			$endcap = null;
			$caption = [];
		}

		$block = [
			'figure',
			'endcap' => $endcap,
			'content' => $this->parseBlocks($content),
			'caption' => $this->parseBlocks(array_values($caption)),
		];

		return [$block, $i];
	}

	/**
	 * Renders a figure
	 */
	protected function renderFigure($block): string
	{
		$caption = $block['endcap'] === null ?
			'' :
			"<figcaption>\n"
				. $this->renderAbsy($block['caption'])
				. "</figcaption>\n" ;

		if ($block['endcap'] === false) {
			$figure = "<figure>\n"
				. $caption . $this->renderAbsy($block['content'])
				. "</figure>\n";
		} else {
			$figure = "<figure>\n"
				. $this->renderAbsy($block['content'])
				. $caption
				. "</figure>\n";
		}

		return $figure;
	}

	abstract protected function parseBlocks($lines);
	abstract protected function renderAbsy($absy);
}

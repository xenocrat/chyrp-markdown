<?php
/**
 * @copyright Copyright 2023-2026 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace xenocrat\markdown\inline;

/**
 * Adds cite inline elements.
 */
trait CiteTrait
{
	protected function parseCiteMarkers(): array
	{
		return array('*_');
	}

	/**
	 * Parses the cite feature.
	 *
	 * @marker *_
	 */
	protected function parseCite($markdown): array
	{
		if (
			preg_match(
				'/^\*_
					# First char cannot be Unicode category Zs, Pe, Pf.
					(?![\s\p{Zs}\p{Pe}\p{Pf}])
					# Contents must not end with a backslash:
					(.*?[^\\\\])
					# Last char cannot be Unicode category Zs, Ps, Pi.
					(?<![\s\p{Zs}\p{Ps}\p{Pi}])
					# End marker:
					_\*/usx',
				str_replace(
					'\\\\',
					'\\\\'.chr(31),
					$markdown
				),
				$matches
			)
		) {
			$matches[0] = str_replace(
				'\\\\'.chr(31),
				'\\\\',
				$matches[0]
			);
			$matches[1] = str_replace(
				'\\\\'.chr(31),
				'\\\\',
				$matches[1]
			);
			$content = $matches[1];
			if (
				// Inline HTML, link, image, or code takes precedence.
				!$this->elementOvershoot(
					$markdown,
					strlen($matches[0]),
					['Lt', 'Link', 'Image', 'inlineCode']
				)
			) {
				return [
					[
						'cite',
						$this->parseInline($content)
					],
					strlen($matches[0])
				];
			}
		}
		return [['text', $markdown[0] . $markdown[1]], 2];
	}

	protected function renderCite($block): string
	{
		return '<cite>'
			. $this->renderAbsy($block[1])
			. '</cite>';
	}

	abstract protected function elementOvershoot($text, $length, $elements);
	abstract protected function renderText($block);
	abstract protected function parseInline($text);
	abstract protected function renderAbsy($blocks);
}

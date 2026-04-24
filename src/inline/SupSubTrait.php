<?php
/**
 * @copyright Copyright 2023-2026 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace xenocrat\markdown\inline;

/**
 * Adds superscript and subscript inline elements.
 */
trait SupSubTrait
{
	protected function parseSupMarkers(): array
	{
		return array('++');
	}

	/**
	 * Parses the superscript feature.
	 *
	 * @marker ++
	 */
	protected function parseSup($markdown): array
	{
		if (
			preg_match(
				'/^\+\+(?!\+)(.*?([^\+\\\\]|(?<=\\\\)\+))\+\+(?!\+)/s',
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
						'sup',
						$this->parseInline($content)
					],
					strlen($matches[0])
				];
			}
		}
		return [['text', $markdown[0] . $markdown[1]], 2];
	}

	protected function renderSup($block): string
	{
		return '<sup>'
			. $this->renderAbsy($block[1])
			. '</sup>';
	}

	protected function parseSubMarkers(): array
	{
		return array('--');
	}

	/**
	 * Parses the subscript feature.
	 *
	 * @marker --
	 */
	protected function parseSub($markdown): array
	{
		if (
			preg_match(
				'/^--(?!-)(.*?([^-\\\\]|(?<=\\\\)-))--(?!-)/s',
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
				// Inline HTML, link, or image takes precedence.
				!$this->elementOvershoot(
					$markdown,
					strlen($matches[0]),
					['Lt', 'Link', 'Image']
				)
			) {
				return [
					[
						'sub',
						$this->parseInline($content)
					],
					strlen($matches[0])
				];
			}
		}
		return [['text', $markdown[0] . $markdown[1]], 2];
	}

	protected function renderSub($block): string
	{
		return '<sub>'
			. $this->renderAbsy($block[1])
			. '</sub>';
	}

	abstract protected function elementOvershoot($text, $length, $elements);
	abstract protected function renderText($block);
	abstract protected function parseInline($text);
	abstract protected function renderAbsy($blocks);
}

<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt, 2024 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace xenocrat\markdown\inline;

/**
 * Adds images, embedded audio and video.
 */
trait MediaLinkTrait
{
	protected function renderImage($block): string
	{
		if (isset($block['refkey'])) {
			if (($ref = $this->lookupReference($block['refkey'])) !== false) {
				$block = array_merge($block, $ref);
			} else {
				if (str_starts_with($block['orig'], '![')) {
					return '!['
					. $this->renderAbsy(
						$this->parseInline(substr($block['orig'], 2))
					);
				}
				return $block['orig'];
			}
		}
		if (
			preg_match('/\.(mpe?g|mp4|m4v|mov|webm|ogv)$/i', $block['url'])
		) {
			return '<video src="'
				. $this->escapeHtmlEntities($block['url'], ENT_COMPAT) . '"'
				. (
					empty($block['title']) ?
						'' :
						' title="'
						. $this->escapeHtmlEntities(
							$block['title'],
							ENT_COMPAT | ENT_SUBSTITUTE
						)
						. '"'
				)
				. '>'
				. $this->renderAbsy($this->parseInline($block['text']))
				. '</video>';
		} elseif (
			preg_match('/\.(mp3|m4a|oga|ogg|spx|wav|aiff?)$/i', $block['url'])
		) {
			return '<audio src="'
				. $this->escapeHtmlEntities($block['url'], ENT_COMPAT) . '"'
				. (
					empty($block['title']) ?
						'' :
						' title="'
						. $this->escapeHtmlEntities(
							$block['title'],
							ENT_COMPAT | ENT_SUBSTITUTE
						)
						. '"'
				)
				. '>'
				. $this->renderAbsy($this->parseInline($block['text']))
				. '</audio>';
		} else {
			return '<img src="'
				. $this->escapeHtmlEntities($block['url'], ENT_COMPAT) . '"'
				. ' alt="'
				. $this->escapeHtmlEntities(
					$block['text'],
					ENT_COMPAT | ENT_SUBSTITUTE
				)
				. '"'
				. (
					empty($block['title']) ?
						'' :
						' title="'
						. $this->escapeHtmlEntities(
							$block['title'],
							ENT_COMPAT | ENT_SUBSTITUTE
						)
						. '"'
					)
				. ($this->html5 ? '>' : ' />');
		}
	}

	abstract protected function parseImage($markdown);
	abstract protected function parseInline($text);
	abstract protected function renderAbsy($blocks);
}

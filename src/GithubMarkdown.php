<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace cebe\markdown;

/**
 * Markdown parser for [GitHub-Flavored Markdown](https://github.github.com/gfm/).
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class GithubMarkdown extends Markdown
{
	// include block element parsing using traits
	use block\TableTrait;

	// include inline element parsing using traits
	use inline\StrikeoutTrait;
	use inline\UrlLinkTrait;

	/**
	 * @var boolean whether to interpret newlines as `<br />`-tags.
	 * This feature is useful for comments where newlines are often meant to be real new lines.
	 */
	public $enableNewlines = false;

	/**
	 * @inheritDoc
	 */
	protected $escapeCharacters = [
		// from Markdown
		'\\', // backslash
		'`', // backtick
		'*', // asterisk
		'_', // underscore
		'{', '}', // curly braces
		'[', ']', // square brackets
		'(', ')', // parentheses
		'#', // hash mark
		'+', // plus sign
		'-', // minus sign (hyphen)
		'.', // dot
		'!', // exclamation mark
		'<', '>',
		// added by GithubMarkdown
		':', // colon
		'|', // pipe
	];

	/**
	 * Consume lines for a paragraph
	 *
	 * Allow other block types to break paragraphs.
	 */
	protected function consumeParagraph($lines, $current): array
	{
		// consume until newline
		$content = [];
		for ($i = $current, $count = count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			if ($line === ''
				|| ltrim($line) === ''
				|| !ctype_alpha($line[0]) && (
					$this->identifyQuote($line, $lines, $i) ||
					$this->identifyFencedCode($line, $lines, $i) ||
					$this->identifyUl($line, $lines, $i) ||
					$this->identifyOl($line, $lines, $i) ||
					$this->identifyHr($line, $lines, $i) ||
					$this->identifyHtml($line, $lines, $i)
				)
				|| $this->identifyHeadline($line, $lines, $i))
			{
				break;
			} else {
				$content[] = $line;
			}
		}
		$block = [
			'paragraph',
			'content' => $this->parseInline(implode("\n", $content)),
		];
		return [$block, --$i];
	}

	/**
	 * @inheritDoc
	 *
	 * Parses a newline indicated by two spaces on the end of a markdown line.
	 */
	protected function renderText($text): string
	{
		if ($this->enableNewlines) {
			$br = $this->html5 ? "<br>\n" : "<br />\n";
			return strtr($text[1], ["  \n" => $br, "\n" => $br]);
		} else {
			return parent::renderText($text);
		}
	}

	/**
	 * @inheritDoc
	 *
	 * Allows escaping newlines to create line breaks.
	 *
	 * @marker \
	 */
	protected function parseEscape($text): array
	{
		$br = $this->html5 ? "<br>\n" : "<br />\n";

		// If the backslash is followed by a newline.
		// Note: GFM doesn't allow spaces after the backslash.
		if (isset($text[1]) && $text[1] === "\n") {
			return [['text', $br], 2];
		} elseif (!isset($text[1])) {
			return [['text', $br], 1];
		}

		// Otherwise parse the sequence normally
		return parent::parseEscape($text);
	}
}
<?php
/**
 * @copyright Copyright 2024 Daniel Pimley
 * @license https://github.com/xenocrat/chyrp-markdown/blob/master/LICENSE
 * @link https://github.com/xenocrat/chyrp-markdown#readme
 */

namespace xenocrat\markdown\inline;

/**
 * Adds checkbox inline elements.
 */
trait CheckboxTrait
{
	protected function parseCheckboxMarkers(): array
	{
		return array('[ ]', '[x]', '[X]', '[~]');
	}

	/**
	 * Parses the checkbox feature.
	 *
	 * @marker [ ]
	 * @marker [x]
	 * @marker [X]
	 * @marker [~]
	 */
	protected function parseCheckbox($markdown): array
	{
		return [
			[
				'checkbox',
				'incomplete' => ($markdown[1] === ' '),
				'inapplicable' => ($markdown[1] === '~')
			],
			3
		];
	}

	protected function renderCheckbox($block): string
	{
		return '<input type="checkbox"'
			. ($block['incomplete'] ? '' : ' checked=""')
			. ($block['inapplicable'] ? ' disabled=""' : '')
			. ($this->html5 ? '>' : ' />');
	}
}

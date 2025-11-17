<?php 

/**
 * default module
 * formatting functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * format a setting
 *
 * @param string $value
 * @param array $cfg
 * @return string
 */
function mf_default_setting_format($value, $cfg = []) {
	$value = trim($value);
	if (array_key_exists('private', $cfg) AND ($cfg['private']))
		return sprintf('<abbr title="%s">******</abbr>',
			wrap_text('The value is only visible during editing.')
		);

	if (str_starts_with($value, '[') AND str_ends_with($value, ']')) {
		$value = substr($value, 1, -1);
		$values = explode(',', $value);
		foreach (array_keys($values) as $index) {
			$values[$index] = trim($values[$index]);
			$values[$index] = zz_list_word_split($values[$index]);
		}
		$value = implode('</li><li>', $values);
		return sprintf('<ul class="default-settings"><li>%s</li></ul>', $value);
	}

	return zz_list_word_split($value);
}

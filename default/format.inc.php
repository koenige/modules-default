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
 * @return string
 */
function mf_default_setting_format($value) {
	$value = trim($value);
	if (str_starts_with($value, '[') AND str_ends_with($value, ']')) {
		$value = substr($value, 1, -1);
		$value = explode(',', $value);
		$value = implode('</li><li>', $value);
		$value = sprintf('<ul class="default-settings"><li>%s</li></ul>', $value);
	}
	return $value;
}

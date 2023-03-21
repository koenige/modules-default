<?php 

/**
 * default module
 * check if JS or CSS files should not be cached
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check if JS or CSS files should not be cached
 *
 * @param $params void
 * @return string
 */
function page_nocache($params) {
	if (!wrap_setting('js_css_nocache')) return '';

	// readable timestamp?
	$timestamp = strtotime(wrap_setting('js_css_nocache'));
	if (!$timestamp) return '';

	$now = time();
	if ($timestamp + wrap_setting('cache_control_text') > $now)
		return '?nocache';

	return '';
}

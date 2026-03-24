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
	if (mf_default_nocache())
		return '?nocache';

	return '';
}

/**
 * check if caching should be disabled
 *
 * @param string $ext (optional, add check in filetypes.cfg)
 * @return bool
 */
function mf_default_nocache($ext = false) {
	if (!wrap_setting('js_css_nocache')) return false;

	// readable timestamp?
	$timestamp = strtotime(wrap_setting('js_css_nocache'));
	if (!$timestamp) return false;
	
	if ($ext) {
		$filetype_cfg = wrap_filetypes($ext);
		$max_age = $filetype_cfg['max-age'] ?? NULL;
	}
	if (!isset($max_age))
		$max_age = wrap_setting('cache_control_text');

	if ($timestamp + $max_age > time()) return true;
	return false;
}

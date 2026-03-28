<?php 

/**
 * default module
 * check if JS or CSS files should not be cached
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021, 2023, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check if JS or CSS files should not be cached
 *
 * @param $params void
 * @return string
 */
function page_nocache($params) {
	wrap_include('functions', 'default');
	if (mf_default_nocache())
		return '?nocache';

	return '';
}

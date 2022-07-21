<?php 

/**
 * default module
 * show search word
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show search word
 *
 * @param $params void
 * @return array $page
 */
function page_searchword($params) {
	if (empty($_GET['q'])) return '';
	return wrap_html_escape($_GET['q']);
}

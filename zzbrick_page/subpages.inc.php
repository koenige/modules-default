<?php

/**
 * default module
 * menu of pages below current page
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * menu of pages below current page
 *
 * @param array $params -
 * @return array $page
 */
function page_subpages() {
	global $zz_page;

	$sql = wrap_sql_query('page_subpages');
	$sql = sprintf($sql, $zz_page['db'][wrap_sql_fields('page_id')]);
	$data = wrap_db_fetch($sql, wrap_sql_fields('page_id'));
	$data = wrap_translate($data, 'webpages');
	return wrap_template('subpages', $data);
}

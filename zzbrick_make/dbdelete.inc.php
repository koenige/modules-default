<?php 

/**
 * default module
 * delete database records that were exported beforehands
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * delete database records that were exported beforehands
 *
 * @param array $params
 * @return array
 */
function mod_default_make_dbdelete($params) {
	$data = [];

	$page['text'] = wrap_template('dbdelete', $data);
	$page['title'] = wrap_text('Database Deletions');
	$page['breadcrumbs'][]['title'] = wrap_text('Database Deletions');
	return $page;
}

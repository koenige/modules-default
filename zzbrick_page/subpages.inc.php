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
function page_subpages(&$params = []) {
	global $zz_page;

	$sql = wrap_sql_query('page_subpages');
	$sql = sprintf($sql, $zz_page['db'][wrap_sql_fields('page_id')]);
	$data = wrap_db_fetch($sql, wrap_sql_fields('page_id'));
	$data = wrap_translate($data, 'webpages');
	
	foreach ($params as $param) {
		if (strstr($param, '=')) {
			$param = explode('=', $param);
			$data[$param[0]] = $param[1];
		}
	}
	$params = [];
	
	foreach ($data as $id => $line) {
		if (!is_numeric($id)) continue;
		if (strstr($line['identifier'], $zz_page['db']['identifier'].'/')) {
			$data[$id]['identifier'] = str_replace(
				$zz_page['db']['identifier'].'/', $zz_page['url']['full']['path'], $line['identifier']
			);
		}
		$access = wrap_access_page($line, $zz_page['access'] ?? [], false);
		if (!$access) {
			unset($data[$id]);
			continue;
		}
		if (!empty($line['parameters'])) {
			parse_str($line['parameters'], $data[$id]['parameters']);
			if (!empty($data[$id]['parameters']['subpages_hidden'])) {
				unset($data[$id]);
				continue;
			}
			if (!empty($data[$id]['parameters']['description']) AND !$line['description'])
				$data[$id]['description'] = rtrim(ltrim($data[$id]['parameters']['description'], '"'), '"');
		}
	}
	return wrap_template('subpages', $data);
}

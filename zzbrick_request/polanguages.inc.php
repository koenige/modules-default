<?php 

/**
 * default module
 * overview of im- and export of language files
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * overview of im- and export of language files
 * 
 * @param array $params
 * @return array $page 'text' for page
 */
function mod_default_polanguages($params) {
	$sql = 'SELECT DISTINCT table_name
	    FROM /*_TABLE default_translationfields _*/
	    WHERE db_name = DATABASE()
	    ORDER BY table_name';
	$data = wrap_db_fetch($sql, 'table_name');
	$data = array_values($data);
	foreach ($data as $index => $line) {
		foreach (wrap_setting('languages_allowed') as $lang) {
			$data[$index]['languages'][]['lang'] = $lang;
		}
	}
	foreach (wrap_setting('languages_allowed') as $lang)
		$data['languages'][]['lang'] = $lang;
	
	$page['text'] = wrap_template('polanguages', $data);
	return $page;
}

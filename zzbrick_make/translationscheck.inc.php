<?php 

/**
 * default module
 * check translations
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check translations
 *
 * @param array $params
 * @global array $zz_setting
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_translationscheck($params) {
	global $zz_setting;
	global $zz_conf;
	if (empty($zz_conf['translations_table'])) return false;
	
	$sql = 'SELECT translationfield_id, db_name, table_name, field_name, field_type
		FROM %s
		ORDER BY translationfield_id';
	$sql = sprintf($sql, $zz_conf['translations_table']);
	$fields = wrap_db_fetch($sql, ['db_name', 'translationfield_id']);

	$sql_t = 'SELECT COUNT(*)
		FROM _translations_%s translations
		LEFT JOIN %s.%s data_table
			ON translations.field_id = data_table.%s
		WHERE translationfield_id = %d
		AND ISNULL(%s)';

	$data = [];
	$current_db = $zz_setting['local_access'] ? $zz_conf['db_name_local'] : $zz_conf['db_name'];
	foreach ($fields as $database => $fields) {
		if ($database !== $current_db) mysqli_select_db($zz_conf['db_connection'], $database);
		$sql = 'SELECT DISTINCT TABLE_NAME, COLUMN_NAME
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE TABLE_SCHEMA = "%s"
			AND INDEX_NAME = "PRIMARY"';
		$sql = sprintf($sql, $database);
		$primary_keys = wrap_db_fetch($sql, '_dummy_', 'key/value');
		foreach ($fields as $field) {
			$sql = sprintf($sql_t
				, $field['field_type']
				, $field['db_name']
				, $field['table_name']
				, $primary_keys[$field['table_name']]
				, $field['translationfield_id']
				, $primary_keys[$field['table_name']]
			);
			$data[$database]['database'] = $database;
			$data[$database]['tables'][$field['translationfield_id']] = $field;
			$data[$database]['tables'][$field['translationfield_id']]['records'] = wrap_db_fetch($sql, '', 'single value');
		}
	}
	$data = array_values($data);
	mysqli_select_db($zz_conf['db_connection'], $current_db);

	$page['text'] = wrap_template('translationscheck', $data);
	$page['title'] = wrap_text('Check Translations');
	$page['breadcrumbs'][] = wrap_text('Check Translations');
	return $page;
}

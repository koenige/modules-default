<?php 

/**
 * default module
 * check translations
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check translations
 *
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_translationscheck($params) {
	global $zz_conf;
	if (!wrap_setting('translate_fields')) return false;
	
	$to_delete = false;
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		foreach (array_keys($_POST) as $key) {
			if (!str_starts_with($key, 'field_id_')) continue;
			$to_delete = substr($key, 9);
		}
	}
	
	$sql = 'SELECT translationfield_id, db_name, table_name, field_name, field_type
		FROM %s
		%s
		ORDER BY translationfield_id';
	$sql = sprintf($sql
		, wrap_sql_table('default_translationfields')
		, $to_delete ? sprintf('WHERE translationfield_id = %d', $to_delete) : ''
	);
	$fields = wrap_db_fetch($sql, ['db_name', 'translationfield_id']);

	$sql_t = 'SELECT %s
		FROM _translations_%s translations
		LEFT JOIN %s.%s data_table
			ON translations.field_id = data_table.%s
		WHERE translationfield_id = %d
		AND ISNULL(%s)';

	$data = [];
	$current_db = wrap_setting('local_access') ? wrap_setting('db_name_local') : $zz_conf['db_name'];
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
				, $to_delete ? 'translation_id' : 'COUNT(*)'
				, $field['field_type']
				, $field['db_name']
				, $field['table_name']
				, $primary_keys[$field['table_name']]
				, $field['translationfield_id']
				, $primary_keys[$field['table_name']]
			);
			if ($to_delete) {
				$deletable_ids = wrap_db_fetch($sql, '_dummy_', 'single value');
				foreach ($deletable_ids as $id) {
					$values = [];
					$values['action'] = 'delete';
					$values['POST']['translation_id'] = $id;
					$ops = zzform_multi('translations-'.$field['field_type'], $values);
				}
				return wrap_redirect_change(sprintf('?deleted=%d', count($deletable_ids)));
			} else {
				$data[$database]['database'] = $database;
				$data[$database]['tables'][$field['translationfield_id']] = $field;
				$data[$database]['tables'][$field['translationfield_id']]['records'] = wrap_db_fetch($sql, '', 'single value');
				$data[$database]['tables'][$field['translationfield_id']]['path'] = wrap_path('default_tables', 'translations-'.$field['field_type']);
			}
		}
	}
	$data = array_values($data);
	if (!empty($_GET['deleted'])) $data['deleted'] = $_GET['deleted'];
	mysqli_select_db($zz_conf['db_connection'], $current_db);

	$page['text'] = wrap_template('translationscheck', $data);
	$page['query_strings'][] = 'deleted';
	$page['title'] = wrap_text('Check Translations');
	$page['breadcrumbs'][] = wrap_text('Check Translations');
	return $page;
}

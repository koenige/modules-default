<?php

/**
 * default module
 * database transfer functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_include('file', 'zzwrap');

/**
 * creates SQL query to read records from database, with all fields
 *
 * @param string $table
 * @param array $conditions
 * @param string $extra_condition
 * @return array
 */
function mf_default_dbtransfer_record_sql($table, $conditions, $extra_condition = '') {
	static $fields = [];
	if (!array_key_exists($table, $fields)) {
		$sql = 'SHOW COLUMNS FROM `%s`';
		$sql = sprintf($sql, $table);
		$table_def = wrap_db_fetch($sql, '_dummy_', 'numeric');
		$fields[$table] = [];
		foreach ($table_def as $field) {
			if (str_starts_with($field['Type'], 'varbinary')) $tpl = 'HEX(`%s`)';
			else $tpl = '`%s`';
			$fields[$table][] = sprintf($tpl, $field['Field']);
		}
	}
	$where = [];
	foreach ($conditions as $line)
		$where[] = vsprintf('`%s` = %d', $line);
	if ($extra_condition) $extra_condition = sprintf(' AND %s', $extra_condition);
	$sql = 'SELECT %s FROM `%s` WHERE (%s) %s';
	return sprintf($sql, implode(', ', $fields[$table]), $table, implode(' OR ', $where), $extra_condition);
}	

/**
 * get a list of database relations
 *
 * @param string $table_name (optional)
 * @param string $field_name (optional)
 * @return array
 */
function mf_default_dbtransfer_relations($table_name = '', $field_name = '') {
	static $relations;
	if (!$relations) {
		$sql = 'SELECT * FROM /*_TABLE zzform_relations _*/';
		$relations = wrap_db_fetch($sql, 'rel_id');
	}
	if (!$table_name) return $relations;
	
	$data = [
		'masters' => [],
		'details' => []
	];
	foreach ($relations as $rel_id => $relation) {
		// @todo add support later
		if ($relation['master_db'] !== wrap_setting('db_name')) continue;
		if ($relation['detail_db'] !== wrap_setting('db_name')) continue;
		if ($relation['master_table'] === $table_name)
			$data['masters'][$rel_id] = $relation;
		if ($relation['detail_table'] === $table_name)
			$data['details'][$rel_id] = $relation;
	}
	return $data;
}

/**
 * get a list of translation fields
 *
 * @return array
 */
function mf_default_dbtransfer_translationfields() {
	$sql = 'SELECT translationfield_id, table_name, field_type
		FROM /*_TABLE default_translationfields _*/
		WHERE db_name = DATABASE()';
	return wrap_db_fetch($sql, 'translationfield_id');
}

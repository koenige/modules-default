<?php

/**
 * default module
 * get all relations of a record
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get all relations of a record
 *
 * @param array $params
 *		string $table
 *		int $record_id
 * @return array
 */
function mod_default_relations($params) {
	if (count($params) !== 2) return false;
	list($table, $record_id) = $params;

	// get all relations
	$sql = 'SELECT rel_id
			, detail_db, detail_table, detail_field, detail_id_field
			, master_table, master_db, master_field
		FROM _relations';
	$relations = wrap_db_fetch($sql, 'rel_id');

	// get only media relations
	$table_prefixed = wrap_db_prefix(sprintf('/*_PREFIX_*/%s', $table));
	$data = [];
	foreach ($relations as $rel_id => $relation) {
		if ($relation['master_db'] !== wrap_setting('db_name')) continue;
		if ($relation['master_table'] !== $table_prefixed) continue;
		
		if (str_ends_with($relation['detail_table'], '_'.$table)) {
			$joined_table = substr($relation['detail_table'], 0, - strlen($table) - 1);
			// check for joins to this master table
			$joined = mod_default_relations_join($relations, $joined_table, $relation['detail_table']);
		} else
			$joined = NULL;

		$sql = 'SELECT %s AS record_id
			FROM `%s`.%s
			WHERE %s = %d';
		$sql = sprintf($sql
			, $joined['id_field'] ?? $relation['detail_id_field']
			, $relation['detail_db']
			, $relation['detail_table']
			, $relation['detail_field']
			, $record_id
		);
		$records = wrap_db_fetch($sql, 'record_id');
		if (!$records) continue;
		
		$data['relations'][$rel_id] = [
			'records' => $records,
			'table' => $joined['master_table'] ?? $relation['master_table'],
			'title' => zz_nice_tablenames($joined['master_table'] ?? $relation['master_table']),
			'form' => wrap_path('default_tables', $joined['master_table'] ?? $relation['master_table'])
		];
	}
	$page['text'] = wrap_template('relations', $data);
	return $page;
}

/**
 * get joined table(s)
 *
 * @param array $relations
 * @param string $master_table
 * @param string $detail_table
 * @return string
 */
function mod_default_relations_join($relations, $master_table, $detail_table) {
	foreach ($relations as $rel_id => $relation) {
		if ($relation['master_table'] !== $master_table) continue;
		if ($relation['detail_table'] !== $detail_table) continue;
		$data = [
			'master_field' => $relation['master_field'],
			'master_table' => $relation['master_table'],
			'id_field' => $relation['detail_field']
		];
		return $data;
	}
	return NULL;
}

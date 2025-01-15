<?php

/**
 * default module
 * export linked database records for a given record
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * export linked database records for a given record
 *
 * @return array
 */
function mod_default_dbexport() {
	$data = $_GET;
	if (!empty($data['table']) AND !empty($data['field']) AND !empty($data['record_id']))
		$data = mod_default_dbexport_read($data);
	
	$page['query_strings'][] = 'table';
	$page['query_strings'][] = 'field';
	$page['query_strings'][] = 'record_id';
	$page['text'] = wrap_template('dbexport', $data);
	return $page;
}

/**
 * read data for a given ID in a record
 *
 * @param array $data
 * @return array
 */
function mod_default_dbexport_read($data) {
	$sql = 'SELECT * FROM `%s` WHERE `%s` = %d';
	$sql = sprintf($sql
		, wrap_db_escape($data['table'])
		, wrap_db_escape($data['field'])
		, wrap_db_escape($data['record_id'])
	);
	// @todo check table and field from structure
	$record = wrap_db_fetch($sql);
	if (!$record) {
		$data['record_not_found'] = true;
		return $data;
	}
	
	wrap_include('file', 'zzwrap');
	$conditions[] = sprintf('`%s` = %d', $data['field'], $data['record_id']);
	$data += mod_default_dbexport_record($data['table'], $data['field'], $conditions);
	return $data;
}

/**
 * read a record and get related records
 *
 * @param string $table
 * @param string $id_field
 * @param array $conditions
 * @param array $data
 */
function mod_default_dbexport_record($table, $id_field, $conditions, $data = []) {
	if (!$data) {
		$data = [
			'saved' => [],
			'conditions' => []
		];
	}
	$sql = 'SELECT * FROM `%s` WHERE %s';
	$sql = sprintf($sql, $table, implode(' OR ', $conditions));
	$records = wrap_db_fetch($sql, $id_field);
	
	$relations = mod_default_dbexport_relations($table);

	$log = wrap_file_log('default/dbexport');
	$table_rel = [];
	// check all relations
	foreach ($records as $record_id => $record) {
		if (!empty($data['saved'][$table][$record_id])) continue; // only once!
		// check if in log
		foreach ($log as $line) {
			if ($line['table'] !== $table) continue;
			if ($line['record_id'] !== $record_id) continue;
			continue 2;
		}
		$data['saved'][$table][$record_id] = $record;
		wrap_file_log('default/dbexport', 'write', [time(), $table, $record_id, json_encode($record)]);
		// get master relations
		foreach ($relations['masters'] as $rel_id => $relation) {
			$table_rel[$relation['detail_table']][] = [
				'foreign_key_field' => $relation['detail_field'],
				'id_field' => $relation['detail_id_field'],
				'id_foreign' => $record_id
			];
		}
		// get detail records relations
		foreach ($relations['details'] as $rel_id => $relation) {
			if (!$record[$relation['detail_field']]) continue;
			$table_rel[$relation['master_table']][] = [
				'id_field' => $relation['master_field'],
				'id' => $record[$relation['detail_field']]
			];
		}
	}
	
	// create WHERE conditions
	foreach ($table_rel as $table => $lines) {
		$where = [];
		foreach ($lines as $line) {
			if (isset($line['foreign_key_field'])) {
				if (!empty($data['conditions'][$table][$line['foreign_key_field']][$line['id_foreign']]))
					continue;
				$where[] = sprintf(
					'`%s` = %d', $line['foreign_key_field'], $line['id_foreign']
				);
				$data['conditions'][$table][$line['foreign_key_field']][$line['id_foreign']] = true;
			} else {
				if (!empty($data['conditions'][$table][$line['id_field']][$line['id']]))
					continue;
				$where[] = sprintf(
					'`%s` = %d', $line['id_field'], $line['id']
				);
				$data['conditions'][$table][$line['id_field']][$line['id']] = true;
			}
		}
		if (!$where) continue;
		$table_id_field = reset($lines);
		$table_id_field = $line['id_field'];
		$data = mod_default_dbexport_record($table, $table_id_field, $where, $data);
	}
	return $data;
}

/**
 * get a list of database relations
 *
 * @param string $table_name (optional)
 * @param string $$field_name (optional)
 * @return array
 */
function mod_default_dbexport_relations($table_name = '', $field_name = '') {
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

<?php

/**
 * default module
 * database import
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * database import
 *
 * @return array
 */
function mod_default_make_dbimport() {
	$data = [];

	wrap_include('file', 'zzwrap');
	$log = wrap_file_log('default/dbexport');
	if (!$log) {
		$data['logfile_missing'] = true;
		$page['text'] = wrap_template('dbimport', $data);
		return $page;
	}
	
	foreach ($log as $line) {
		if (!array_key_exists($line['table'], $data))
			$data[$line['table']] = [
				'table' => $line['table'],
				'records' => 1,
				'logged' => mod_default_dbimport_log($line['table'], 'count')
			];
		else
			$data[$line['table']]['records']++;
	}
	// mark as complete where records = logged
	foreach ($data as $index => $line)
		if ($line['logged'] === $line['records'])
			$data[$index]['complete'] = true;
	ksort($data);
	$data = array_values($data);
	if (empty($_GET['table'])) $data['overview'] = true;
	else $data = mod_default_dbimport_table($data, $log);

	$page['query_strings'][] = 'table';
	if (!empty($_GET['table'])) {
		global $zz_page;
		$page['breadcrumbs'][] = [
			'url_path' => './',
			'title' => $zz_page['db']['title']
		];
		$page['breadcrumbs'][]['title'] = $_GET['table'];
	}
	$page['text'] = wrap_template('dbimport', $data);
	return $page;
}

function mod_default_dbimport_table($data, $log) {
	ini_set('max_execution_time', 0);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		mod_default_dbimport_table_save();
	}

	$tabledata = [];
	$conditions = [];
	$data['table'] = $_GET['table'];
	$data['auto_increment'] = wrap_db_increment($data['table']);
	foreach ($log as $line) {
		if ($line['table'] !== $data['table']) continue;
		$tabledata[$line['record_id']] = json_decode($line['record'], true);
		$conditions[] = [key($tabledata[$line['record_id']]), $line['record_id']];
	}
	$data['records'] = count($tabledata);
	
	
	wrap_include('zzbrick_request/dbexport', 'default');
	$sql = mod_default_dbexport_record_sql($data['table'], $conditions);
	
	$data['relations'] = [];
	$relations = mod_default_dbexport_relations();
	foreach ($relations as $rel) {
		if ($rel['master_table'] === $data['table'])
			$data['id_field'] = $rel['master_field'];
		if ($rel['detail_table'] === $data['table'])
			$data['relations'][] = $rel;
	}
	
	$id_field = wrap_edit_sql($sql, 'SELECT');
	$id_field = explode(' ', $sql);
	$id_field = trim(trim(trim($id_field[1]), ','), '`');
	$existing = wrap_db_fetch($sql, $id_field);
	
	$diffs = [];
	$data['new'] = 0;
	$data['different'] = 0;
	$data['different_logged'] = 0;
	$data['identical'] = 0;
	foreach ($tabledata as $record_id => $line) {
		if (!array_key_exists($record_id, $existing)) {
			mod_default_dbimport_log($data['table'], 'write', $record_id);
			$data['new']++;
			continue;
		}
		mod_default_dbimport_diff($data, $record_id, $line, $existing[$record_id]);
	}
	if ($data['identical'] AND $data['identical'] === $data['records']) {
		$data['all_identical'] = true;
		return $data;
	} elseif (!$data['identical']) {
		$data['none_identical'] = true;
		return $data;
	}

	return $data;
}

/**
 * save ID matching in logfile
 *
 * @param string $table
 * @param string $action 'write', 'check', 'count'
 * @param int $old_record_id
 * @param int $new_record_id (optional)
 * @return bool false: record ID exists, true: record ID does not exist
 */
function mod_default_dbimport_log($table, $action, $old_record_id = 0, $new_record_id = 0) {
	static $increment = [];
	static $log = [];
	if (!array_key_exists($table, $increment))
		$increment[$table] = wrap_db_increment($table);
	$logfile = sprintf('default/dbimport_ids[%s]', $table);
	if (!array_key_exists($table, $log))
		$log[$table] = wrap_file_log($logfile);
	if ($action === 'count') return count($log[$table]);

	if (!$new_record_id) {
		// already in log?
		foreach ($log[$table] as $line) {
			if ($line['old_record_id'].'' === $old_record_id.'') return false;
			if ($line['new_record_id'] >= $increment[$table])
				$increment[$table] = ++$line['new_record_id'];
		}
		if ($action === 'write')
			$new_record_id = $increment[$table]++;
	}

	if ($action === 'write')
		wrap_file_log($logfile, 'write', [time(), $old_record_id, $new_record_id]);
	return true;
}

/**
 * check if a record is different, if it was already manually checked etc.
 *
 * @param array $data
 * @param int $record_id
 * @param array $record
 * @param array $record_existing
 */
function mod_default_dbimport_diff(&$data, $record_id, $record, $record_existing) {
	static $unique_fields = [];
	// check: already logged?
	$not_logged = mod_default_dbimport_log($data['table'], 'check', $record_id);
	if (!$not_logged) {
		$data['different_logged']++;
		return;
	}
	
	// check: identical?
	unset($record['last_update']);
	unset($record_existing['last_update']);
	if ($record === $record_existing) {
		mod_default_dbimport_log($data['table'], 'write', $record_id, $record_id);
		$data['identical']++;
	}
	
	// check: unique fields different? if yes, record is different
	if (wrap_setting('default_dbimport_diff_unique_fields')) {
		if (!$unique_fields) {
			foreach (wrap_setting('default_dbimport_diff_unique_fields') as $field) {
				$field = explode('.', $field);
				$unique_fields[$field[0]][] = $field[1];
			}
		}
	}
	if (array_key_exists($data['table'], $unique_fields)) {
		foreach ($unique_fields[$data['table']] as $field) {
			if ($record[$field] !== $record_existing[$field]) {
				mod_default_dbimport_log($data['table'], 'write', $record_id);
				$data['different_logged']++;
				return;	
			}
		}
	}
	
	// check: m:n-table, one value different = always completely different
	if (in_array($data['table'], wrap_setting('default_dbimport_diff_mntables'))) {
		mod_default_dbimport_log($data['table'], 'write', $record_id);
		$data['different_logged']++;
		return;	
	}

	// check: are all values identical apart from the ID?
	$completely_different = true;
	$record_2 = $record;
	array_shift($record_2);
	foreach ($record_2 as $field_name => $value) {
		// ignore fields with NULL values on both sides
		if ($record_existing[$field_name] === $value AND $value) {
			$completely_different = false;
			break;
		}
	}
	if ($completely_different) {
		mod_default_dbimport_log($data['table'], 'write', $record_id);
		$data['different_logged']++;
		return;	
	}
	
	$data['different']++;
	// show what is different, old vs. new record, just one per time
	if (!empty($data['diff'])) return;
	$data['diff'] = [];
	$data['diff_record_id'] = $record_id;
	foreach ($record as $field_name => $value) {
		$data['diff'][] = [
			'field' => $field_name,
			'new_value' => $value ?? wrap_text('– none –'),
			'old_value' => $record_existing[$field_name] ?? wrap_text('– none –'),
			'identical' => ($value.'' === $record_existing[$field_name].'') ? true : false
		];
	}
}

/**
 * manually save ID matching in logfile
 *
 */
function mod_default_dbimport_table_save() {
	if (array_key_exists('identical', $_POST))
		mod_default_dbimport_log($_GET['table'], 'write', $_POST['record_id'], $_POST['record_id']);
	else
		mod_default_dbimport_log($_GET['table'], 'write', $_POST['record_id']);
	wrap_redirect_change('#diff');
}

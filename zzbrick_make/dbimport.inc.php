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
			$data[$line['table']] = ['table' => $line['table'], 'records' => 1];
		else
			$data[$line['table']]['records']++;
	}
	ksort($data);
	$data = array_values($data);
	if (empty($_GET['table'])) $data['overview'] = true;
	else $data = mod_default_dbimport_table($data, $log);

	$page['query_strings'][] = 'table';
	$page['text'] = wrap_template('dbimport', $data);
	return $page;
}

function mod_default_dbimport_table($data, $log) {
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
	$data['identical'] = 0;
	$data['records_new'] = [];
	$data['records_different'] = [];
	foreach ($tabledata as $record_id => $line) {
		if (!array_key_exists($record_id, $existing)) {
			mod_default_dbimport_log($data['table'], 'write', $record_id);
			$data['new']++;
			$data['records_new'][$record_id] = $line;
			continue;
		}
		unset($line['last_update']);
		unset($existing[$record_id]['last_update']);
		if ($line === $existing[$record_id]) {
			mod_default_dbimport_log($data['table'], 'write', $record_id, $record_id);
			$data['identical']++;
		} else {
			$data['different']++;
			// show what is different, old vs. new record, just one per time
			if (empty($data['diff']) AND mod_default_dbimport_log($data['table'], 'check', $record_id)) {
				$data['diff'] = [];
				$data['diff_record_id'] = $record_id;
				foreach ($line as $field_name => $value) {
					$data['diff'][] = [
						'field' => $field_name,
						'new_value' => $value,
						'old_value' => $existing[$record_id][$field_name] ?? NULL,
						'identical' => ($value.'' === $existing[$record_id][$field_name].'' ?? NULL) ? true : false
					];
				}
			}
			$data['records_different'][$record_id] = $line;
		}
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
 * @param string $action
 * @param int $old_record_id
 * @param int $new_record_id (optional)
 * @return bool false: record ID exists, true: record ID does not exist
 */
function mod_default_dbimport_log($table, $action, $old_record_id, $new_record_id = 0) {
	static $increment = 0;
	if (!$increment) $increment = wrap_db_increment($table);

	$logfile = sprintf('default/dbimport_ids[%s]', $table);
	$log = wrap_file_log($logfile);
	if (!$new_record_id) {
		// already in log?
		foreach ($log as $line) {
			if ($line['old_record_id'].'' === $old_record_id.'') return false;
			if ($line['new_record_id'] >= $increment)
				$increment = ++$line['new_record_id'];
		}
		$new_record_id = $increment++;
	}

	if ($action === 'write')
		wrap_file_log($logfile, 'write', [time(), $old_record_id, $new_record_id]);
	return true;
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
}

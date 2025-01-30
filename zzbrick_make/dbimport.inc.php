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
	wrap_include('file', 'zzwrap');
	$data = [];

	if ($_SERVER['REQUEST_METHOD'] === 'POST' AND empty($_GET['table'])) {
		if (!empty($_POST['delete'])) {
			$first = key($_POST['delete']);
			$filename = sprintf('%s/default/dbimport_ids-%s.log', wrap_setting('log_dir'), key($_POST['delete']));
			if (file_exists($filename)) unlink($filename);
			wrap_redirect_change();
		} elseif (!empty($_POST['import'])) {
			$data = mod_default_make_dbimport_go(key($_POST['import']));
		}
	} else {
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
		$no_import = NULL;
		foreach ($data as $index => $line) {
			if ($line['logged'] === $line['records'])
				$data[$index]['complete'] = true;
			else
				$no_import = true;
			if ($line['logged'])
				$data[$index]['deletion_possible'] = true;
		}
		ksort($data);
		$data = array_values($data);
		if ($no_import) $data['no_import'] = true;
		if (empty($_GET['table'])) $data['overview'] = true;
		else $data = mod_default_dbimport_table($data, $log);
	}

	$page['query_strings'][] = 'table';
	if (!empty($_GET['table']) OR !empty($_POST)) {
		global $zz_page;
		$page['breadcrumbs'][] = [
			'url_path' => './',
			'title' => $zz_page['db']['title']
		];
		$page['breadcrumbs'][]['title'] = $_GET['table'] ?? key($_POST['import']);
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
 * @return int ID of new record, or count of records
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
			if ($line['old_record_id'].'' === $old_record_id.'') return (int)$line['new_record_id'];
			if ($line['new_record_id'] >= $increment[$table])
				$increment[$table] = ++$line['new_record_id'];
		}
		if ($action === 'write')
			$new_record_id = $increment[$table]++;
	}

	if ($action === 'write')
		wrap_file_log($logfile, 'write', [time(), $old_record_id, $new_record_id]);
	return $new_record_id;
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
	$new_record_id = mod_default_dbimport_log($data['table'], 'check', $record_id);
	if ($new_record_id === $record_id) {
		$data['different_logged']++;
		return;
	} elseif ($new_record_id > wrap_db_increment($data['table'])) {
		$data['new']++;
		return;
	}
	
	// check: identical?
	unset($record['last_update']);
	unset($record_existing['last_update']);
	if ($record === $record_existing) {
		mod_default_dbimport_log($data['table'], 'write', $record_id, $record_id);
		$data['identical']++;
		return;
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
				$data['new']++;
				return;	
			}
		}
	}
	
	// check: m:n-table, one value different = always completely different
	if (in_array($data['table'], wrap_setting('default_dbimport_diff_mntables'))) {
		mod_default_dbimport_log($data['table'], 'write', $record_id);
		$data['new']++;
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
		$data['new']++;
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

/**
 * import files after preparation is complete
 *
 * @param string $table
 * @return array
 */
function mod_default_make_dbimport_go($table) {
	wrap_include('zzbrick_request/dbexport', 'default');
	$relations = mod_default_dbexport_relations();
	$fields = [];
	$ids = [];
	$id_field = '';
	foreach ($relations as $index => $relation) {
		if ($relation['detail_db'] !== wrap_setting('db_name')) continue;
		if ($relation['detail_table'] !== $table) continue;
		// replace own ID
		$id_field = $relation['detail_id_field'];
		$fields[$id_field] = $relation['detail_table'];
		// replace foreign ID
		$fields[$relation['detail_field']] = $relation['master_table'];
	}
	if (!$id_field) {
		// no detail relations, look for ID field in master relations
		foreach ($relations as $index => $relation) {
			if ($relation['detail_db'] !== wrap_setting('db_name')) continue;
			if ($relation['master_table'] !== $table) continue;
			$id_field = $relation['master_field'];
		}
	}
	
	foreach ($fields as $related_table) {
		if (!array_key_exists($related_table, $ids))
			$ids[$related_table] = mod_default_make_dbimport_ids($related_table);
	}

	// get existing IDs, won’t be imported
	$sql = 'SELECT %s FROM %s';
	$sql = sprintf($sql, $id_field, $table);
	$table_ids = wrap_db_fetch($sql, $id_field, 'single value');
	
	// check for identifiers?
	$identifiers = [];
	$identifier_field_name = NULL;
	foreach (wrap_setting('default_dbimport_identifiers') as $identifier) {
		if (!str_starts_with($identifier, $table.'.')) continue;
		$sql = 'SELECT %s, %s FROM %s';
		$sql = sprintf($sql, $id_field, $identifier, $table);
		$identifiers = wrap_db_fetch($sql, $id_field, 'key/value');
		$identifier_field_name = substr($identifier, strpos($identifier, '.') + 1);
	}

	$data['imported'] = 0;
	$logfile = wrap_file_log('default/dbexport');
	wrap_include('database', 'zzform');
	foreach ($logfile as $line) {
		if ($line['table'] !== $table) continue;
		$field_names = [];
		$values = [];
		$id = 0;
		$line['record'] = json_decode($line['record'], true);
		foreach ($line['record'] as $field_name => $value) {
			$function = '';
			if (str_starts_with($field_name, 'HEX(`')) {
				$function = 'UNHEX(%s)';
				$field_name = ltrim($field_name, 'HEX(`');
				$field_name = rtrim($field_name, '`)');
			}
			$field_names[] = sprintf('`%s`', $field_name);
			if ($field_name === $identifier_field_name) {
				// check if identifier already exists, add suffix in case it does
				if (in_array($value, $identifiers))
					$value = sprintf('%s%s', $value, wrap_setting('default_dbimport_identifiers_suffix'));
			}
			if ($value AND array_key_exists($field_name, $fields)) {
				if (empty($ids[$fields[$field_name]][$value])) {
					wrap_error(sprintf('DB Import failed. No replacement found for %s.%s %d. Record: %s'
						, $fields[$field_name], $field_name, $value, json_encode($line['record'])
					), E_USER_ERROR);
				}
				$value = $ids[$fields[$field_name]][$value];
			}
			if (!$id) $id = (int)$value; // first field
			if (is_null($value)) $value = 'NULL';
			elseif (!wrap_is_int($value)) $value = sprintf('"%s"', wrap_db_escape($value));
			$values[] = $function ? sprintf($function, $value) : $value;
		}
		// already in database? ignore
		if (in_array($id, $table_ids)) continue;
		$sql = sprintf(
			'INSERT INTO %s (%s) VALUES (%s)'
			, $table
			, implode(', ', $field_names)
			, implode(', ', $values)
		);
		$success = wrap_db_query($sql);
		if (empty($success['id']))
			wrap_error(sprintf('DB Import failed. Query: %s', $sql), E_USER_ERROR);
		if ($success['id'] !== $id)
			wrap_error(sprintf(
				'DB Import returned with wrong increment. ID received: %d, ID expected: %d, query: %s'
				, $success['id'], $id, $sql
			), E_USER_ERROR);
		zz_db_log($sql, wrap_setting('default_dbimport_log_username'), $id);
		$data['imported']++;
	}
	$data['table'] = $table;
	return $data;
}

/**
 * get replacements for IDs per table
 *
 * @param string $table
 * @return array
 */
function mod_default_make_dbimport_ids($table) {
	static $data = [];
	if (array_key_exists($table, $data)) return $data[$table];

	$log =	wrap_file_log(sprintf('default/dbimport_ids[%s]', $table));
	$data[$table] = [];
	foreach ($log as $line)
		$data[$table][$line['old_record_id']] = $line['new_record_id'];
	return $data[$table];
}

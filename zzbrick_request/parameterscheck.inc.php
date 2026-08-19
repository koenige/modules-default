<?php 

/**
 * default module
 * check parameter fields against settings.cfg
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 *
 * Variables
 * translate_pot = admin
 */


/**
 * scan parameter fields in the database against settings.cfg
 *
 * @param array $params
 * @return array $page
 */
function mod_default_parameterscheck($params) {
	wrap_access_quit('default_maintenance');

	$data = mf_default_parameterscheck_scan();
	$data['undefined'] = array_values($data['undefined']);
	$data['defined'] = mf_default_parameterscheck_aggregate_defined($data['defined']);

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('parameterscheck', $data);
	$page['title'] = wrap_text('Check Parameters');
	$page['breadcrumbs'][]['title'] = wrap_text('Check Parameters');
	return $page;
}

/**
 * collect parameter usage from all tables with parameter fields
 *
 * @return array undefined, defined, tables_scanned
 */
function mf_default_parameterscheck_scan() {
	$settings = wrap_cfg_files('settings');
	$tables = mf_default_parameterscheck_tables();
	$undefined = [];
	$defined = [];

	foreach ($tables as $table) {
		$records = mf_default_parameterscheck_records($table);
		if (!$records) continue;

		$field_name = $table['parameter_field'];
		foreach ($records as $record) {
			$scope = mf_default_parameterscheck_table_scope($table['table'], $record);
			$keys = mf_default_parameterscheck_parse_keys($record[$field_name]);
			foreach ($keys as $key) {
				$row = [
					'table' => $table['table'],
					'record' => $record['record_id'],
					'field' => $field_name,
					'context' => $record['context'],
					'parameter' => $key,
				];
				$config = mf_default_parameterscheck_key_config($key, $settings);
				if (!$config) {
					$undefined[] = $row;
					continue;
				}
				$row['module'] = $config['package'] ?? '';
				$row['scope'] = mf_default_parameterscheck_scope_text($config);
				$row['applies'] = wrap_setting_from_table_applies($scope, $config);
				$defined[] = $row;
			}
		}
	}

	usort($undefined, 'mf_default_parameterscheck_sort_rows');
	usort($defined, 'mf_default_parameterscheck_sort_rows');

	return [
		'undefined' => $undefined,
		'defined' => $defined,
		'tables_scanned' => count($tables),
	];
}

/**
 * count registered parameter occurrences (no per-record rows)
 *
 * @param array $defined
 * @return array
 */
function mf_default_parameterscheck_aggregate_defined(array $defined) {
	$groups = [];
	foreach ($defined as $row) {
		$key = sprintf(
			'%s\0%s\0%s\0%s\0%s\0%s\0%s',
			$row['table'],
			$row['field'],
			$row['context'],
			$row['parameter'],
			$row['module'],
			$row['scope'],
			$row['applies'] ? '1' : '0'
		);
		if (!array_key_exists($key, $groups)) {
			unset($row['record']);
			$row['count'] = 0;
			$groups[$key] = $row;
		}
		$groups[$key]['count']++;
	}
	$defined = array_values($groups);
	usort($defined, 'mf_default_parameterscheck_sort_rows');
	return $defined;
}

/**
 * tables with a parameter field from zzbrick_tables/index.json
 *
 * @return array table name => entry
 */
function mf_default_parameterscheck_tables() {
	$tables = [];
	$files = wrap_collect_files('zzbrick_tables/index.json');
	foreach ($files as $package => $file) {
		$index = json_decode(file_get_contents($file), true);
		if (!is_array($index)) continue;
		foreach ($index as $table => $entry) {
			if (empty($entry['parameter_field'])) continue;
			$tables[$table] = [
				'table' => $table,
				'title' => $entry['title'] ?? $table,
				'parameter_field' => $entry['parameter_field'],
				'package' => $package,
			];
		}
	}
	ksort($tables);
	return $tables;
}

/**
 * rows with non-empty parameter field values
 *
 * @param array $table
 * @return array
 */
function mf_default_parameterscheck_records(array $table) {
	wrap_include('database', 'zzform');

	$table_name = $table['table'];
	$field_name = $table['parameter_field'];
	$db_table = zz_db_table('/*_PREFIX_*/'.$table_name);
	$primary_key = wrap_mysql_primary_key($table_name);
	if (!$primary_key) return [];

	$sql = sprintf(
		'SELECT `%s`, `%s` FROM %s WHERE `%s` != "" AND `%s` IS NOT NULL'
		, $primary_key, $field_name
		, zz_db_table_backticks($db_table['db_name'].'.'.$db_table['table'])
		, $field_name
		, $field_name
	);
	$rows = wrap_db_fetch($sql, $primary_key);
	if (!$rows) return [];
	
	$context = [];
	if ($table['table'] === 'categories') {
		$categories = wrap_id_read('categories', '', 'aliases');
		foreach ($categories as $path => $id)
			if ($pos = strpos($path, '/'))
				$context[$id] = substr($path, 0, $pos);
			else
				$context[$id] = $path;
	}

	$records = [];
	foreach ($rows as $row) {
		$record = [
			'record_id' => $row[$primary_key],
			'context' => $context[$row[$primary_key]] ?? '',
			$field_name => $row[$field_name] ?? '',
		];
		$records[] = $record;
	}
	return $records;
}

/**
 * scope string for wrap_setting_from_table_applies()
 *
 * @param string $table
 * @param array $record
 * @return string
 */
function mf_default_parameterscheck_table_scope($table, array $record) {
	if ($table !== 'categories') return $table;
	if ($record['context'] === '') return 'categories';
	return 'categories/'.$record['context'];
}

/**
 * parameter keys from a parameter string, in settings.cfg notation
 *
 * @param string $string
 * @return array
 */
function mf_default_parameterscheck_parse_keys($string) {
	if (!$string) return [];
	parse_str(ltrim($string, '&'), $params);
	if (!$params) return [];
	return mf_default_parameterscheck_flatten_keys($params);
}

/**
 * flatten parsed parameters to keys like font_file[bold]
 *
 * @param array $params
 * @param string $prefix
 * @return array
 */
function mf_default_parameterscheck_flatten_keys(array $params, $prefix = '') {
	$keys = [];
	foreach ($params as $key => $value) {
		$name = $prefix ? $prefix.'['.$key.']' : $key;
		if (is_array($value)) {
			$keys = array_merge($keys, mf_default_parameterscheck_flatten_keys($value, $name));
			continue;
		}
		$keys[] = $name;
	}
	return $keys;
}

/**
 * settings.cfg entry for a parameter key
 *
 * try the full key, then strip bracket segments from the end until one matches
 *
 * @param string $key
 * @param array $settings
 * @return array|null
 */
function mf_default_parameterscheck_key_config($key, array $settings) {
	$registered = mf_default_parameterscheck_key_registered($key, $settings);
	if (!$registered) return NULL;
	return $settings[$registered];
}

/**
 * registered settings.cfg key for a parameter, if any
 *
 * @param string $key
 * @param array $settings
 * @return string
 */
function mf_default_parameterscheck_key_registered($key, array $settings) {
	$candidate = $key;
	while ($candidate !== '') {
		if (array_key_exists($candidate, $settings)) return $candidate;
		if (!str_contains($candidate, '[')) break;
		$candidate = preg_replace('/\[[^\]]*\]$/', '', $candidate);
	}
	return '';
}

/**
 * scope[] values as readable text
 *
 * @param array $config
 * @return string
 */
function mf_default_parameterscheck_scope_text(array $config) {
	if (empty($config['scope'])) return '';
	$scope = $config['scope'];
	if (!is_array($scope)) return $scope;
	return implode(', ', $scope);
}

/**
 * sort result rows for stable output
 *
 * @param array $left
 * @param array $right
 * @return int
 */
function mf_default_parameterscheck_sort_rows(array $left, array $right) {
	foreach (['table', 'context', 'parameter', 'record', 'field'] as $key) {
		$compare = strcmp((string) ($left[$key] ?? ''), (string) ($right[$key] ?? ''));
		if ($compare !== 0) return $compare;
	}
	return 0;
}

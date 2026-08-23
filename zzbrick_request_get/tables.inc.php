<?php 

/**
 * default module
 * Table definitions index from zzbrick_tables scan
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * collect table metadata from zzbrick_tables/*.php
 *
 * @param string $package
 * @return array table => entry
 */
function mf_default_tables_collect($package) {
	$files = wrap_collect_files('zzbrick_tables/*.php', $package);
	if (!$files) return [];

	$entries = [];
	foreach ($files as $file) {
		$parsed = mf_default_tables_parse_file($file);
		if (!$parsed) continue;
		$entries[key($parsed)] = reset($parsed);
	}
	ksort($entries);
	return $entries;
}

/**
 * Is zzbrick_tables/index.json up to date for one package?
 *
 * @param string $package
 * @return string empty|missing|current|outdated
 */
function mf_default_tables_index_status($package) {
	wrap_include('index', 'default');
	return mf_default_index_status($package, 'zzbrick_tables/index.json', 'mf_default_tables_collect');
}

/**
 * parse one zzbrick_tables script without including it
 *
 * @param string $file
 * @return array|null table => entry
 */
function mf_default_tables_parse_file($file) {
	$raw = file_get_contents($file);
	if ($raw === false) {
		wrap_error(['Table definition %s is not readable.', ['values' => [$file]]]);
		return NULL;
	}
	if (!preg_match('/\$zz\[\'table\'\]\s*=\s*\'([^\']*)\'/', $raw, $matches))
		return NULL;

	$table = mf_default_tables_table_name($matches[1]);
	$title = $table;
	if (preg_match('/\$zz\[\'title\'\]\s*=\s*\'([^\']*)\'/', $raw, $matches))
		$title = $matches[1];

	$entry = ['title' => $title];
	$parameter = mf_default_tables_parameter_field($raw);
	if (!$parameter) return [$table => $entry];

	$entry['parameter_field'] = $parameter['field_name'];
	$entry['audience'] = $parameter['audience'];
	if (!$entry['audience']) {
		wrap_include('help', 'default');
		$entry['audience'] = mf_default_help_audience($raw);
	}
	if (!$entry['audience']) $entry['audience'] = ['editor'];
	return [$table => $entry];
}

/**
 * database table name from $zz['table'] literal
 *
 * @param string $literal
 * @return string
 */
function mf_default_tables_table_name($literal) {
	if (preg_match('#/\*_PREFIX_\*/(.+)#', $literal, $matches))
		return $matches[1];
	if (preg_match('#/\*_TABLE\s+(\S+)\s+_\*/#', $literal, $matches))
		return $matches[1];
	return $literal;
}

/**
 * parameter field from table script source
 *
 * @param string $raw
 * @return array|null field_name, audience
 */
function mf_default_tables_parameter_field($raw) {
	if (!preg_match_all(
		'/\$zz\[\'fields\'\]\[(\d+)\]\[\'type\'\]\s*=\s*\'parameter\'/',
		$raw,
		$matches
	)) return NULL;

	$field_no = $matches[1][0];
	$field_name = NULL;
	if (preg_match(
		'/\$zz\[\'fields\'\]\['.$field_no.'\]\[\'field_name\'\]\s*=\s*\'([^\']*)\'/',
		$raw,
		$name_matches
	)) $field_name = $name_matches[1];
	if (!$field_name) return NULL;

	$audience = [];
	if (preg_match(
		'/\$zz\[\'fields\'\]\['.$field_no.'\]\[\'audience\'\]\s*=\s*\'([^\']*)\'/',
		$raw,
		$audience_matches
	)) {
		wrap_include('help', 'default');
		$audience = mf_default_help_audience_list($audience_matches[1]);
	}

	return [
		'field_name' => $field_name,
		'audience' => $audience,
	];
}

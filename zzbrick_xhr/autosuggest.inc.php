<?php

/**
 * clubs module
 * XHR for clubs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/clubs
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2021, 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check given query for matching autosuggest values
 *
 * @param array $request
 * @param array $def
 *		string 'sql'
 *		array 'sql_fields'
 * @return array
 */
function mod_default_xhr_autosuggest($request, $def) {
	if (!is_array($def)) wrap_quit(403, 'Not usable as standalone command.');

	$text = mb_strtolower($request['text']);
	$limit = $request['limit'] + 1;
	$concat = ' | ';
	$equal = substr($text, -1) === ' ' ? true : false;
	$text = trim($text);
	if (strstr($text, $concat)) {
		$text = explode($concat, $text);
	} else {
		$text = [$text];
	}

	$collation = '_utf8';
	foreach ($def['sql_fields'] as $no => $sql_field) {
		foreach ($text as $index => $value) {
			$query = $equal ? 'LOWER(%s) = %s"%s"' : 'LOWER(%s) LIKE %s"%%%s%%"';
			$where[$index][] = sprintf($query, $sql_field, $collation, wrap_db_escape($value));
		}
	}
	$conditions = [];
	foreach ($where as $condition) {
		$conditions[] = sprintf('(%s)', implode(' OR ', $condition));
	}
	if (str_starts_with(trim($def['sql']), 'SHOW')) {
		$def['sql'] .= sprintf(' LIKE "%%%s%%"', $text[0]);
	} else {
		$def['sql'] = wrap_edit_sql($def['sql'], !empty($def['sql_having']) ? 'HAVING' : 'WHERE', implode(' AND ', $conditions));
	}
	if ($def['sql_fields']) {
	 	$id_field_name = $def['sql_fields'][0];
		if (strstr($id_field_name, '.'))
			$id_field_name = substr($id_field_name, strrpos($id_field_name, '.') + 1);
		$records = wrap_db_fetch($def['sql'], $id_field_name);
	} else {
		$records = wrap_db_fetch($def['sql'], '_dummy_', 'numeric');
	}
	$records = array_values($records);
	$data = [];
	if (count($records) > $limit) {
		// more records than we might show
		$data['entries'] = [];
		$data['entries'][] = ['text' => htmlspecialchars($request['text'])];
		$data['entries'][] = [
			'text' => wrap_text('Please enter more characters.'),
			'elements' => [
				0 => [
					'node' => 'div',
					'properties' => [
						'className' => 'xhr_foot',
						'text' => wrap_text('Please enter more characters.')
					]
				]
			]
		];
		return $data;
	}

	if (!$records) {
		$data['entries'][] = ['text' => htmlspecialchars($request['text'])];
		$data['entries'][] = [
			'text' => wrap_text('No record was found.'),
			'elements' => [
				0 => [
					'node' => 'div',
					'properties' => [
						'className' => 'xhr_foot',
						'text' => wrap_text('No record was found.')
					]
				]
			]
		];
		return $data;
	}
	
	$i = 0;
	foreach ($records as $record) {
		$j = 0;
		$text = [];
		// remove ID
		array_shift($record);
		if (!empty($def['sql_ignore']))
			foreach ($def['sql_ignore'] as $field) unset($record[$field]);
		// search entry for zzform, concatenated and space at the end
		$data['entries'][$i]['text'] = implode($concat, $record).' ';
		$i++;
	}
	return $data;
}

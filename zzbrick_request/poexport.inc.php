<?php 

/**
 * default module
 * export translations to PO file
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * export translations to PO file
 * 
 * @param array $params
 * @return array $page 'text' for page
 */
function mod_default_poexport($params) {
	if (count($params) !== 1) return false;
	
	if (str_ends_with($params[0], '.po')) {
		list($table, $language) = explode('-', substr($params[0], 0, -3));
	} elseif (str_ends_with($params[0], '.pot')) {
		$table = substr($params[0], 0, -4);
		$language = 'template';
	} else {
		return false;
	}
	
	if (!$table) return false;
	if (!$language) return false;
	
	$data = [];
	$sql = 'SELECT translationfield_id, field_name, field_type, table_name
	    FROM /*_TABLE default_translationfields _*/
	    WHERE db_name = DATABASE()
	    AND table_name = "%s"';
	$sql = sprintf($sql, wrap_db_escape($table));
	$data['fields'] = wrap_db_fetch($sql, 'translationfield_id');
	if (!$data['fields']) return false;

	$fields = [];
	foreach ($data['fields'] as $line) {
		$fields[] = $line['field_name'];
	}

	// get primary key
	$primary_key = '';
	$unique_keys = [];
	$sql = 'SHOW KEYS FROM %s WHERE Non_unique = 0';
	$sql = sprintf($sql, wrap_db_escape($table));
	$keys = wrap_db_fetch($sql, '_dummy_', 'numeric');
	foreach ($keys as $key) {
		if ($key['Key_name'] === 'PRIMARY') {
			$primary_key = $key['Column_name'];
			$fields[] = $key['Column_name'];
		} else {
			$unique_keys[] = $key['Column_name'];
			$fields[] = $key['Column_name'];
		}
	}
	if (!$primary_key) return false;

	// get untranslated values
	$sql = 'SELECT %s
		FROM %s';
	$sql = sprintf($sql, implode(', ', $fields), wrap_db_escape($table));
	$data['existing'] = wrap_db_fetch($sql, $primary_key);
	
	// get translated values
	if ($language !== 'template') {
		foreach ($data['fields'] as $translationfield_id => $translationfield) {
			$sql = 'SELECT translation_id, field_id, translation
				FROM _translations_%s
				WHERE language_id = %d
				AND translationfield_id = %d';
			$sql = sprintf($sql
				, $translationfield['field_type']
				, wrap_id('languages', $language)
				, $translationfield['translationfield_id']
			);
			$translations = wrap_db_fetch($sql, 'field_id');
			foreach ($translations as $field_id => $translation)
				$data['translations'][$field_id][$translationfield['field_name']] = $translation['translation'];
		}
	}
	
	// create .pot / .po file
	$content = [];
	$table_name = reset($data['fields']);
	$content['table_name'] = $table_name['table_name'];
	$content['lang'] = $language !== 'template' ? $language : NULL;
	foreach ($data['existing'] as $record_id => $line) {
		unset($line[$primary_key]);
		$unique_key = [];
		foreach ($unique_keys as $field) {
			$unique_key[] = $line[$field];
			unset($line[$field]);
		}
		$unique_key = implode('-', $unique_key);
		foreach ($line as $field_name => $value) {
			if (is_string($value)) $value = trim($value);
			if (!$value) continue;
			$content[] = [
				'field_id' => $record_id,
				'field_name' => $field_name,
				'text' => mod_default_poexport_escape($value),
				'translation' => mod_default_poexport_escape($data['translations'][$record_id][$field_name] ?? NULL),
				'identifier' => $unique_key,
			];
		}
	}
	$page['text'] = wrap_template('po', $content);
	$page['content_type'] = 'po';
	$page['headers']['filename'] = $params[0];
	return $page;	
}

/**
 * escape some characters for export
 *
 * @param string $string
 * @return string
 */
function mod_default_poexport_escape($string) {
	if (!$string) return $string;
	$string = str_replace('"', '\"', $string);
	$string = str_replace("\n", '\n', $string);
	return $string;
}

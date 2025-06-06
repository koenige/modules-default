<?php 

/**
 * default module
 * Table definition for 'translations' (text)
 * Tabellendefinition für Übersetzungen (text)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2013, 2018-2020, 2022-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$language_translations = wrap_setting('language_translations');
$standard_lang = reset($language_translations);

$zz_sub['title'] = 'Translation';
$zz_sub['show_title'] = false;
$zz_sub['type'] = 'subtable';
$zz_sub['hide_in_list'] = true;
$zz_sub['min_records'] = 1;
$zz_sub['max_records_sql'] = 'SELECT COUNT(*) FROM /*_PREFIX_*/languages 
	WHERE (iso_639_1 <> "/*_SETTING default_source_language _*/" OR NOT ISNULL(variation))
	AND website = "yes"';

$zz_sub['table'] = '/*_PREFIX_*/_translations_text';
$zz_sub['table_name'] = 'translations_text';
// just show record if condition 99 is true (or undefined)
// nur Anzeigen des Datensatzes bei Bedingung 99
$zz_sub['unless'][99]['access'] = 'show';

$zz_sub['subselect']['sql'] = 'SELECT field_id, CONCAT(iso_639_1, IFNULL(CONCAT("-", variation), "")) AS lang,
		translation
	FROM /*_PREFIX_*/_translations_text
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)';
$zz_sub['subselect']['concat_fields'] = ': ';

$zz_sub['fields'][1]['title'] = 'ID';
$zz_sub['fields'][1]['field_name'] = 'translation_id';
$zz_sub['fields'][1]['type'] = 'id';

$zz_sub['fields'][2]['title'] = 'Translation field';
$zz_sub['fields'][2]['field_name'] = 'translationfield_id';
$zz_sub['fields'][2]['type'] = 'translation_key';
$zz_sub['fields'][2]['type_detail'] = 'select';
$zz_sub['fields'][2]['sql'] = 'SELECT translationfield_id
		, CONCAT(db_name, " | ", table_name, " | ", field_name) AS translationfield
	FROM /*_TABLE default_translationfields _*/
	WHERE field_type = "text"';
$zz_sub['fields'][2]['display_field'] = 'translationfield';
$zz_sub['fields'][2]['exclude_from_search'] = true;
$zz_sub['fields'][2]['if']['where']['hide_in_list'] = true;

$zz_sub['fields'][3]['title'] = 'ID';
$zz_sub['fields'][3]['field_name'] = 'field_id';
$zz_sub['fields'][3]['type'] = 'foreign_key';

$zz_sub['fields'][5]['field_name'] = 'language_id';
$zz_sub['fields'][5]['type'] = 'select';
$zz_sub['fields'][5]['sql'] = sprintf('SELECT language_id, language_%s, variation
	FROM /*_PREFIX_*/languages 
	WHERE iso_639_1 <> "/*_SETTING default_source_language _*/"
	AND website = "yes"
	ORDER BY language_%s'
	, in_array(wrap_setting('lang'), wrap_setting('language_translations')) ? wrap_setting('lang') : $standard_lang
	, in_array(wrap_setting('lang'), wrap_setting('language_translations')) ? wrap_setting('lang') : $standard_lang
);
$zz_sub['fields'][5]['prefix'] = wrap_text('Translation to').' ';
$zz_sub['fields'][5]['suffix'] = ': ';
$zz_sub['fields'][5]['default'] = wrap_language_id(wrap_setting('default_translation_language'));
$zz_sub['fields'][5]['def_val_ignore'] = true;
$zz_sub['fields'][5]['show_title'] = false;
$zz_sub['fields'][5]['display_field'] = 'lang';
$zz_sub['fields'][5]['search'] = 'CONCAT(iso_639_1, IFNULL(CONCAT("-", variation), ""))';
$zz_sub['fields'][5]['character_set'] = 'utf8';
$zz_sub['fields'][5]['exclude_from_search'] = true;

$zz_sub['fields'][4]['title'] = 'Translation';
$zz_sub['fields'][4]['show_title'] = false;
$zz_sub['fields'][4]['field_name'] = 'translation';
$zz_sub['fields'][4]['type'] = 'memo';
$zz_sub['fields'][4]['inherit_format'] = true;
$zz_sub['fields'][4]['unless'][1]['list_prefix'] = '<del>';
$zz_sub['fields'][4]['unless'][1]['list_suffix'] = '</del>';

$zz_sub['subtitle']['translationfield_id']['sql'] = $zz_sub['fields'][2]['sql'];
$zz_sub['subtitle']['translationfield_id']['var'] = ['translationfield'];

$zz_sub['sql'] = 'SELECT /*_PREFIX_*/_translations_text.*
		, CONCAT(iso_639_1, IFNULL(CONCAT("-", variation), "")) AS lang
		, CONCAT(db_name, " | ", table_name, " | ", field_name) AS translationfield
	FROM /*_PREFIX_*/_translations_text
	LEFT JOIN /*_TABLE default_translationfields _*/
		USING (translationfield_id)
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)
	ORDER BY db_name, table_name, field_name, iso_639_1, variation
';

if (empty($_GET['order']) OR $_GET['order'] === 'translationfield')
	$zz_sub['list']['group'] = 'translationfield';

if (!empty($_GET['where']['translationfield_id'])) {
	$sql = 'SELECT db_name, table_name, field_name, field_type
		FROM /*_TABLE default_translationfields _*/
		WHERE translationfield_id = %d';
	$sql = sprintf($sql, $_GET['where']['translationfield_id']);
	$translation_field = wrap_db_fetch($sql);
	if ($translation_field AND $translation_field['field_type'] === 'text') {
		$sql = 'SELECT DISTINCT COLUMN_NAME
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE TABLE_SCHEMA = "%s"
			AND TABLE_NAME = "%s"
			AND INDEX_NAME = "PRIMARY"';
		$sql = sprintf($sql
			, $translation_field['db_name']
			, $translation_field['table_name']
		);
		$key_field_name = wrap_db_fetch($sql, '', 'single value');
	
		$zz_sub['conditions'][1]['scope'] = 'query';
		$zz_sub['conditions'][1]['sql'] = 'SELECT translation_id
			FROM %s.%s source_table
			LEFT JOIN /*_PREFIX_*/_translations_text
				ON source_table.%s = /*_PREFIX_*/_translations_text.field_id';
		$zz_sub['conditions'][1]['sql'] = sprintf($zz_sub['conditions'][1]['sql']
			, $translation_field['db_name']
			, $translation_field['table_name']
			, $key_field_name
		);
		$zz_sub['conditions'][1]['key_field_name'] = 'translation_id';
	}
}

// if not used as a subtable:
$zz_sub['init_ignore_log'] = [
	'show_title', 'type', 'hide_in_list', 'min_records', 'max_records_sql', 'table_name',
	'form_display'
];

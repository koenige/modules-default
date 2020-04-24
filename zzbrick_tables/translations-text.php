<?php 

/**
 * default module
 * Table definition for 'translations' (text)
 * Tabellendefinition für Übersetzungen (text)
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2013, 2018-2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz_sub['title'] = 'Translation';
$zz_sub['show_title'] = false;
$zz_sub['type'] = 'subtable';
$zz_sub['hide_in_list'] = true;
$zz_sub['min_records'] = 1;
$zz_sub['max_records_sql'] = sprintf(
	'SELECT COUNT(language_id) FROM /*_PREFIX_*/languages 
	WHERE (iso_639_1 <> "%s" OR !ISNULL(variation))
	AND website = "yes"', $zz_setting['default_source_language']
);

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
	FROM _translationfields
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
	WHERE iso_639_1 <> "%s"
	AND website = "yes"
	ORDER BY language_%s'
	, in_array($zz_setting['lang'], $zz_setting['language_translations']) ? $zz_setting['lang'] : reset($zz_setting['language_translations'])
	, $zz_setting['default_source_language']
	, in_array($zz_setting['lang'], $zz_setting['language_translations']) ? $zz_setting['lang'] : reset($zz_setting['language_translations'])
);
$zz_sub['fields'][5]['prefix'] = wrap_text('Translation to').' ';
$zz_sub['fields'][5]['suffix'] = ': ';
if (!empty($zz_setting['default_translation_language']))
	$zz_sub['fields'][5]['default'] = wrap_language_id($zz_setting['default_translation_language']);
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

$zz_sub['subtitle']['translationfield_id']['sql'] = $zz_sub['fields'][2]['sql'];
$zz_sub['subtitle']['translationfield_id']['var'] = ['translationfield'];

$zz_sub['sql'] = 'SELECT /*_PREFIX_*/_translations_text.*
		, CONCAT(iso_639_1, IFNULL(CONCAT("-", variation), "")) AS lang
		, CONCAT(db_name, " | ", table_name, " | ", field_name) AS translationfield
	FROM /*_PREFIX_*/_translations_text
	LEFT JOIN '.$zz_conf['translations_table'].'
		USING (translationfield_id)
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)
	ORDER BY db_name, table_name, field_name, iso_639_1, variation
';

if (empty($_GET['order']) OR $_GET['order'] === 'translationfield')
	$zz_sub['list']['group'] = 'translationfield';

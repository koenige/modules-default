<?php 

/**
 * default module
 * Database table to set translation fields
 * DB-Tabelle zur Eingabe von Feldern, die übersetzt werden sollen
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2005-2010, 2014, 2019-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_access('default_translations')) wrap_quit(403);

$zz['title'] = 'Translations';
$zz['table'] = $zz_conf['translations_table'];

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'translationfield_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Database';
$zz['fields'][2]['field_name'] = 'db_name';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SHOW DATABASES';
$zz['fields'][2]['default'] = $zz_conf['db_name'];
$zz['fields'][2]['hide_in_list'] = true;

$zz['fields'][3]['title'] = 'Table';
$zz['fields'][3]['field_name'] = 'table_name';
if (!empty($_POST['db_name'])) {
	$zz['fields'][3]['type'] = 'select';	
	$zz['fields'][3]['sql'] = sprintf('SHOW TABLES FROM %s', wrap_db_escape($_POST['db_name']));
} else
	$zz['fields'][3]['type'] = 'text';	
$zz['fields'][3]['list_append_next'] = true;
$zz['fields'][3]['list_suffix'] = ' . ';

$zz['fields'][4]['title'] = 'Field';
$zz['fields'][4]['field_name'] = 'field_name';
if (!empty($_POST['db_name']) AND !empty($_POST['table_name'])) {
	$zz['fields'][4]['type'] = 'select';
	$zz['fields'][4]['sql'] = sprintf('SHOW COLUMNS FROM %s.%s', wrap_db_escape($_POST['db_name']), wrap_db_escape($_POST['table_name']));
	$zz['fields'][4]['sql_index_only'] = true;
} else
	$zz['fields'][4]['type'] = 'text';
	
$zz['fields'][9]['title'] = 'Data type';
$zz['fields'][9]['field_name'] = 'field_type';		
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum_title'] = [wrap_text('text line'), wrap_text('memo')];
$zz['fields'][9]['enum'] = ['varchar', 'text'];
	
$zz['sql'] = sprintf('SELECT * FROM %s
	ORDER BY db_name, table_name, field_name'
	, $zz_conf['translations_table']
);

$zz_conf['max_select'] = 100;

$zz['details'][1]['title'] = 'Translations';
$zz['details'][1]['referer'] = 1;
$zz['if'][1]['details'][1]['link'] = 'translations-varchar?where[translationfield_id]=';
$zz['if'][1]['details'][1]['sql'] = 'SELECT COUNT(*) FROM /*_PREFIX_*/_translations_varchar WHERE translationfield_id = %d';
$zz['if'][2]['details'][1]['link'] = 'translations-text?where[translationfield_id]=';
$zz['if'][2]['details'][1]['sql'] = 'SELECT COUNT(*) FROM /*_PREFIX_*/_translations_text WHERE translationfield_id = %d';

$zz['conditions'][1]['scope'] = 'record';
$zz['conditions'][1]['where'] = 'field_type = "varchar"';

$zz['conditions'][2]['scope'] = 'record';
$zz['conditions'][2]['where'] = 'field_type = "text"';

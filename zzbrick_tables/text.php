<?php 

/**
 * default module
 * Database table for translations of text blocks
 * DB-Tabelle zur Eingabe von Uebersetzungen von Textbloecken
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2009-2010, 2013-2016, 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_access('default_text')) wrap_quit(403);

wrap_setting('default_source_language', wrap_setting('default_source_language_text_db'));

$zz['title'] = 'Text';
$zz['table'] = wrap_sql_table('default_text');

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'text_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'text';
$zz['fields'][2]['type'] = 'write_once';
$zz['fields'][2]['type_detail'] = 'text';
$zz['fields'][2]['translation']['hide_in_list'] = false;
$zz['fields'][2]['typo_remove_double_spaces'] = true;

$zz['fields'][3]['title'] = 'More Text';
$zz['fields'][3]['field_name'] = 'more_text';
$zz['fields'][3]['type'] = 'memo';
// activate if needed
$zz['fields'][3]['hide_in_list'] = true;
$zz['fields'][3]['hide_in_form'] = true;

$zz['fields'][4]['field_name'] = 'area';
if (!empty($_GET['filter']['area']))
	$zz['fields'][4]['hide_in_list'] = true;
else
	$zz['fields'][4]['group_in_list'] = true;

$zz['fields'][20]['title'] = 'Last Update';
$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = 'SELECT * FROM '.wrap_sql_table('default_text').'
	ORDER BY area, text';

$zz['filter'][1]['title'] = wrap_text('Area');
$zz['filter'][1]['identifier'] = 'area';
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'area';
$zz['filter'][1]['field_name'] = 'area';
$zz['filter'][1]['sql'] = 'SELECT DISTINCT area, area
	FROM '.wrap_sql_table('default_text');
	
$zz['export'] = 'CSV Excel';

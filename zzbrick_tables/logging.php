<?php 

/**
 * default module
 * Logging of the database operations via zzform, function zzlog()
 * Protokoll der Datenbankeingaben mittels zzform, Funktion zz_log
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2007-2010, 2014, 2017-2018, 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_access('default_logging')) wrap_quit(403);

$zz['title'] = 'Logging';
$zz['table'] = wrap_sql_table('zzform_logging');

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'log_id';
$zz['fields'][1]['type'] = 'id';
$zz['fields'][1]['show_id'] = true;

$zz['fields'][2]['field_name'] = 'query';
$zz['fields'][2]['class'] = 'block480a hyphenate';
$zz['fields'][2]['list_format'] = 'htmlspecialchars';

if (wrap_setting('zzform_logging_id')) {
	$zz['fields'][3]['title'] = 'Record';
	$zz['fields'][3]['field_name'] = 'record_id';
	$zz['fields'][3]['type'] = 'number';
	$zz['fields'][3]['class'] = 'hidden480';
}

$zz['fields'][4]['field_name'] = 'user';
$zz['fields'][4]['class'] = 'hidden480';

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'display';
$zz['fields'][99]['type_detail'] = 'timestamp';
$zz['fields'][99]['class'] = 'block480';

$zz['sql'] = 'SELECT * FROM '.wrap_sql_table('zzform_logging');
$zz['sqlorder'] = ' ORDER BY log_id DESC';

$zz_conf['max_select'] = 200;
$zz_conf['limit'] = 20;
$zz_conf['add'] = false;
$zz_conf['edit'] = false;
$zz_conf['delete'] = false;

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
 * @copyright Copyright © 2007-2010, 2014, 2017-2018, 2021-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_access_quit('default_logging');

$zz['title'] = 'Logging';
$zz['table'] = '/*_TABLE zzform_logging _*/';

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

$zz['sql'] = 'SELECT * FROM /*_TABLE zzform_logging _*/';
$zz['sqlorder'] = ' ORDER BY log_id DESC';

$zz['setting']['zzform_max_select'] = 200;
$zz['setting']['zzform_limit'] = 20;
$zz['record']['add'] = false;
$zz['record']['edit'] = false;
$zz['record']['delete'] = false;

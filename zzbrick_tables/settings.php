<?php 

/**
 * default module
 * Database table for settings (website settings, user settings)
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2008-2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Settings';
$zz['table'] = '/*_PREFIX_*/_settings';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'setting_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'login_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT login_id, username 
	FROM /*_PREFIX_*/logins
	ORDER BY username';
$zz['fields'][2]['display_field'] = 'username';
$zz['fields'][2]['search'] = '/*_PREFIX_*/logins.username';

$zz['fields'][3]['title'] = 'Key';
$zz['fields'][3]['type'] = 'write_once';
$zz['fields'][3]['field_name'] = 'setting_key';
$zz['fields'][3]['list_append_next'] = true;

$zz['fields'][6]['field_name'] = 'explanation';
$zz['fields'][6]['type'] = 'memo';
$zz['fields'][6]['rows'] = 3;
$zz['fields'][6]['list_prefix'] = '<p class="explanation" style="margin: .75em 2.5em; max-width: 40em; "><em>';
$zz['fields'][6]['list_suffix'] = '</em></p>';

$zz['fields'][4]['title'] = 'Value';
$zz['fields'][4]['field_name'] = 'setting_value';
$zz['fields'][4]['null'] = true;
$zz['fields'][4]['null_string'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/_settings.*, /*_PREFIX_*/logins.username
	FROM /*_PREFIX_*/_settings
	LEFT JOIN /*_PREFIX_*/logins USING (login_id)';
$zz['sqlorder'] = ' ORDER BY username, setting_key, setting_value';

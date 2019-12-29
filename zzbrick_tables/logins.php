<?php 

/**
 * default module
 * Table definition for 'logins' (passwords, who's online)
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2012, 2016, 2018-2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Logins';
$zz['table'] = '/*_PREFIX_*/logins';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'login_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][11] = false; // person_id

$zz['fields'][2]['field_name'] = 'username';
$zz['fields'][2]['type'] = 'text';
$zz['fields'][2]['class'] = 'block480a';

$zz['fields'][6]['title_tab'] = 'Rights';
$zz['fields'][6]['field_name'] = 'login_rights';
$zz['fields'][6]['type'] = 'select';
$zz['fields'][6]['default'] = 'read and write';
$zz['fields'][6]['enum'] = ['admin', 'read and write', 'read'];
$zz['fields'][6]['enum_title'] = ['Technik', 'Redaktion', 'Gast'];
$zz['fields'][6]['show_values_as_list'] = true;

$zz['fields'][3]['field_name'] = 'password';
$zz['fields'][3]['type'] = 'password';
$zz['fields'][3]['sql_password_check'] = 'SELECT /*_PREFIX_*/logins.password 
	FROM /*_PREFIX_*/logins WHERE login_id = ';
$zz['fields'][3]['hide_in_list'] = 'true';

$zz['fields'][13] = false; // random password

$zz['fields'][9]['title'] = 'Change Pwd?';
$zz['fields'][9]['field_name'] = 'password_change';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = ['yes', 'no'];
$zz['fields'][9]['default'] = 'yes';
$zz['fields'][9]['hide_in_list'] = true;
$zz['fields'][9]['explanation'] = '"Yes" means that the user has to change the password next time he or she logs in.';

$zz['fields'][4]['field_name'] = 'logged_in';
$zz['fields'][4]['type'] = 'display';
$zz['fields'][4]['translate_field_value'] = true;
$zz['fields'][4]['class'] = 'hidden480';
$zz['fields'][4]['if']['add']['hide_in_form'] = true;

$zz['fields'][5]['title'] = 'Click';
$zz['fields'][5]['field_name'] = 'last_click';
$zz['fields'][5]['type'] = 'display';
$zz['fields'][5]['type_detail'] = 'datetime';
$zz['fields'][5]['explanation'] = 'Last activity in database';
$zz['fields'][5]['class'] = 'hidden480';
$zz['fields'][5]['if']['add']['hide_in_form'] = true;

$zz['fields'][10]['field_name'] = 'active';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = ['yes', 'no'];
$zz['fields'][10]['default'] = 'yes';
$zz['fields'][10]['explanation'] = 'To deactivate a login';
$zz['fields'][10]['if']['add']['hide_in_form'] = true;

$zz['fields'][12] = false; // password reminder

$zz['fields'][99]['title'] = 'Updated';
$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/logins.*
	, IF(ISNULL(last_click), last_click, FROM_UNIXTIME(last_click, "%Y-%m-%d %H:%i")) AS last_click
	FROM /*_PREFIX_*/logins
';
$zz['sqlorder'] = ' ORDER BY username';

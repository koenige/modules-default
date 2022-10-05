<?php 

/**
 * default module
 * Table definition for 'logins' (passwords, who's online)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2012, 2016, 2018-2019, 2021-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Logins';
$zz['table'] = '/*_PREFIX_*/logins';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'login_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][11] = []; // contact_id
if (wrap_get_setting('login_with_contact_id')) {
	$zz['fields'][11]['field_name'] = 'contact_id';
	$zz['fields'][11]['type'] = 'select';
	$zz['fields'][11]['sql'] = 'SELECT contact_id, contact, identifier
		FROM /*_PREFIX_*/contacts
		ORDER BY identifier';
	$zz['fields'][11]['sql_character_set'][1] = 'utf8'; 
	$zz['fields'][11]['display_field'] = 'contact';
	$zz['fields'][11]['link'] = [
		'function' => 'mf_contacts_profile_path',
		'fields' => ['identifier', 'contact_parameters']
	];
	$zz['fields'][11]['unique'] = true;
	$zz['fields'][11]['class'] = 'block480a';
	$zz['fields'][11]['character_set'] = 'utf8';
}

if (wrap_access('default_masquerade')) {
	$zz['fields'][19]['field_name'] = 'user_id'; // extend query to include this
	$zz['fields'][19]['type'] = 'display';
	$zz['fields'][19]['link'] = [
		'area' => 'default_masquerade',
		'fields' => ['user_id']
	];
	$zz['fields'][19]['exclude_from_search'] = true;
	$zz['fields'][19]['hide_in_form'] = true;
	$zz['fields'][19]['hide_in_list_if_empty'] = true;
	$zz['fields'][19]['class'] = 'block480a number';
}

if (wrap_get_setting('login_with_contact_id')) {
	$zz['fields'][2]['title'] = 'Username';
	$zz['fields'][2]['field_name'] = 'username';
	$zz['fields'][2]['type'] = 'display';
	$zz['fields'][2]['search'] = '/*_PREFIX_*/contacts.identifier';
	$zz['fields'][2]['character_set'] = 'latin1';
} else {
	$zz['fields'][2]['field_name'] = 'username';
	$zz['fields'][2]['type'] = 'text';
	$zz['fields'][2]['class'] = 'block480a';
}

if (wrap_get_setting('login_with_login_rights')) {
	$zz['fields'][6]['title_tab'] = 'Rights';
	$zz['fields'][6]['field_name'] = 'login_rights';
	$zz['fields'][6]['type'] = 'select';
	$zz['fields'][6]['default'] = 'read and write';
	$zz['fields'][6]['enum'] = ['admin', 'read and write', 'read'];
	$zz['fields'][6]['enum_title'] = ['Technik', 'Redaktion', 'Gast'];
	$zz['fields'][6]['show_values_as_list'] = true;
}

$zz['fields'][3]['field_name'] = 'password';
$zz['fields'][3]['type'] = 'password';
$zz['fields'][3]['sql_password_check'] = 'SELECT /*_PREFIX_*/logins.password 
	FROM /*_PREFIX_*/logins WHERE login_id = ';
$zz['fields'][3]['hide_in_list'] = 'true';

$zz['fields'][13] = []; // random password

$zz['fields'][9]['title'] = 'Change Pwd?';
$zz['fields'][9]['field_name'] = 'password_change';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = ['yes', 'no'];
$zz['fields'][9]['default'] = 'yes';
$zz['fields'][9]['hide_in_list'] = true;
$zz['fields'][9]['explanation'] = '“Yes” means that the user has to change the password next time he or she logs in.';
$zz['fields'][9]['class'] = 'hidden480';

$zz['fields'][4]['title_tab'] = '<abbr title="Is user online">Online?</abbr>';
$zz['fields'][4]['field_name'] = 'logged_in';
$zz['fields'][4]['type'] = 'display';
$zz['fields'][4]['translate_field_value'] = true;
$zz['fields'][4]['class'] = 'hidden480';
$zz['fields'][4]['explanation'] = 'Is user logged in or did not log out last time?';
$zz['fields'][4]['if']['add']['hide_in_form'] = true;

$zz['fields'][5]['title'] = 'Click';
$zz['fields'][5]['field_name'] = 'last_click';
$zz['fields'][5]['type'] = 'display';
$zz['fields'][5]['type_detail'] = 'datetime';
$zz['fields'][5]['explanation'] = 'Last activity online';
$zz['fields'][5]['class'] = 'hidden480';
$zz['fields'][5]['if']['add']['hide_in_form'] = true;

$zz['fields'][10]['field_name'] = 'active';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = ['yes', 'no'];
$zz['fields'][10]['default'] = 'yes';
$zz['fields'][10]['explanation'] = 'To deactivate a login';
$zz['fields'][10]['if']['add']['hide_in_form'] = true;

$zz['fields'][12] = []; // password reminder

$zz['fields'][99]['title'] = 'Updated';
$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/logins.*
		, IF(ISNULL(last_click), last_click, FROM_UNIXTIME(last_click, "%Y-%m-%d %H:%i")) AS last_click
	FROM /*_PREFIX_*/logins
';
$zz['sqlorder'] = ' ORDER BY username';

if (wrap_get_setting('login_with_contact_id')) {
	$zz['sql'] = 'SELECT /*_PREFIX_*/logins.*
			, /*_PREFIX_*/contacts.contact_id AS user_id
			, contact, identifier
			, /*_PREFIX_*/contacts.identifier AS username
			, IF(ISNULL(last_click), last_click, FROM_UNIXTIME(last_click, "%Y-%m-%d %H:%i")) AS last_click
			, contact_categories.parameters AS contact_parameters
		FROM /*_PREFIX_*/logins
		LEFT JOIN /*_PREFIX_*/persons USING (contact_id)
		LEFT JOIN /*_PREFIX_*/contacts USING (contact_id)
		LEFT JOIN /*_PREFIX_*/categories contact_categories
			ON contact_categories.category_id = /*_PREFIX_*/contacts.contact_category_id
	';
	$zz['sqlorder'] = ' ORDER BY last_click DESC, contact';
}

if (!wrap_access('default_logins_full')) {
	$zz['access'] = 'none';
}

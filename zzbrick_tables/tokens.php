<?php 

/**
 * default module
 * Database table for tokens
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Tokens';
$zz['table'] = '/*_PREFIX_*/tokens';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'token_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][4]['field_name'] = 'login_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT login_id, username
	FROM /*_PREFIX_*/logins
	ORDER BY username';
$zz['fields'][4]['display_field'] = 'username';
if (wrap_get_setting('login_with_contact_id')) {
	$zz['fields'][4]['sql'] = 'SELECT login_id, identifier AS username
		FROM /*_PREFIX_*/logins logins
		LEFT JOIN /*_PREFIX_*/contacts contacts USING (contact_id)
		ORDER BY identifier';
	$zz['fields'][4]['search'] = '/*_PREFIX_*/contacts.identifier';
}

$zz['fields'][2]['title'] = 'Access Token';
$zz['fields'][2]['field_name'] = 'access_token';
$zz['fields'][2]['type'] = 'identifier';
$zz['fields'][2]['fields'] = ['login_id'];
$zz['fields'][2]['conf_identifier']['function'] = 'wrap_random_hash';
$zz['fields'][2]['conf_identifier']['function_parameter'][] = 192;
$zz['fields'][2]['conf_identifier']['function_parameter'][] = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz-';
$zz['fields'][2]['hide_in_list'] = true;

$zz['fields'][3]['title'] = 'Access Token Expires';
$zz['fields'][3]['field_name'] = 'access_token_expires';
$zz['fields'][3]['type'] = 'datetime';
$zz['fields'][3]['default'] = date('Y-m-d H:i:s', time() + 86400 * 365 * 4);

$zz['fields'][5]['title'] = 'Client Identifier';
$zz['fields'][5]['field_name'] = 'client_identifier';
$zz['fields'][5]['type'] = 'identifier';
$zz['fields'][5]['fields'] = ['login_id', 'client_identifier'];
$zz['fields'][5]['conf_identifier']['function'] = 'wrap_random_hash';
$zz['fields'][5]['conf_identifier']['function_parameter'][] = 36;
$zz['fields'][5]['conf_identifier']['function_parameter'][] = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz-';

$zz['fields'][6]['title'] = 'Client Secret';
$zz['fields'][6]['field_name'] = 'client_secret';
$zz['fields'][6]['type'] = 'identifier';
$zz['fields'][6]['fields'] = ['login_id', 'client_secret'];
$zz['fields'][6]['conf_identifier']['function'] = 'wrap_random_hash';
$zz['fields'][6]['conf_identifier']['function_parameter'][] = 36;
$zz['fields'][6]['conf_identifier']['function_parameter'][] = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz-';
$zz['fields'][6]['hide_in_list'] = true;

$zz['fields'][7]['title'] = 'Refresh Token';
$zz['fields'][7]['field_name'] = 'refresh_token';
$zz['fields'][7]['type'] = 'identifier';
$zz['fields'][7]['fields'] = ['login_id'];
$zz['fields'][7]['conf_identifier']['function'] = 'wrap_random_hash';
$zz['fields'][7]['conf_identifier']['function_parameter'][] = 36;
$zz['fields'][7]['conf_identifier']['function_parameter'][] = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz-';
$zz['fields'][7]['hide_in_list'] = true;

$zz['fields'][8]['field_name'] = 'created';
$zz['fields'][8]['type'] = 'write_once';
$zz['fields'][8]['if']['add']['type'] = 'hidden';
$zz['fields'][8]['type_detail'] = 'datetime';
$zz['fields'][8]['default'] = date('Y-m-d H:i:s');

$zz['fields'][99]['title'] = 'Last Update';
$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';

$zz['sql'] = 'SELECT /*_PREFIX_*/tokens.*
		, /*_PREFIX_*/logins.username
	FROM /*_PREFIX_*/tokens
	LEFT JOIN /*_PREFIX_*/logins USING (login_id)
';
$zz['sqlorder'] = 'ORDER BY username';

if (wrap_get_setting('login_with_contact_id')) {
	$zz['sql'] = 'SELECT /*_PREFIX_*/tokens.*
			, /*_PREFIX_*/contacts.identifier AS username
		FROM /*_PREFIX_*/tokens
		LEFT JOIN /*_PREFIX_*/logins USING (login_id)
		LEFT JOIN /*_PREFIX_*/contacts USING (contact_id)
	';
	$zz['sqlorder'] = 'ORDER BY identifier';
}


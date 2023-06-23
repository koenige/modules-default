<?php

/**
 * default module
 * Database form for own password
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2018, 2020-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz = zzform_include('logins');

// just allow access for login table to own login ID
$zz['where']['login_id'] = $_SESSION['login_id'];

// 2 = username
$zz['fields'][2]['hide_in_form'] = true;	// username

// 3 = password
$zz['fields'][3]['type'] = 'password_change';
unset($zz['fields'][3]['function']); // no password function, e. g. random pwd
if (!empty($_SESSION['dont_require_old_password'])) {
	$zz['fields'][3]['dont_require_old_password'] = true;
}

// 9 = password_change
if (isset($zz['fields'][9])) {
	$zz['fields'][9]['type'] = 'hidden';
	$zz['fields'][9]['hide_in_form'] = true;
	$zz['fields'][9]['value'] = 'no';
}

// 99 = last_update
$zz['fields'][99]['hide_in_form'] = true;

foreach (array_keys($zz['fields']) as $no) {
	if (in_array($no, [1, 2, 3, 9, 99])) continue;
	unset($zz['fields'][$no]);
}

$zz['title'] = 'Change Password';
$zz['explanation'] = markdown(
	'### '.wrap_text('Hints for secure passwords')
	."\n\n".wrap_text('password-rules')
);
$zz['access'] = 'edit_only';
$zz['hooks']['after_update'] = 'mf_default_password_update';
$zz['record']['no_timeframe'] = true;

if (!empty($_GET['url']))
	$zz_conf['redirect']['successful_update'] = $_GET['url'];
if (empty($_GET['referer']))
	$zz_conf['referer'] = wrap_domain_path('login_entry');

wrap_text_set('Edit a record', 'Change My Password');


function mf_default_password_update() {
	if (empty($_SESSION['dont_require_old_password'])) return;
	$success = wrap_session_start();
	$_SESSION['dont_require_old_password'] = false;
	session_write_close();
}

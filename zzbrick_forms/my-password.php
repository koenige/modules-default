<?php

/**
 * default module
 * Database form for own password
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2018, 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (file_exists($zz_conf['form_scripts'].'/logins.php')) {
	require_once $zz_conf['form_scripts'].'/logins.php';
} else {
	require_once __DIR__.'/../zzbrick_tables/logins.php';
}

if (empty($zz) AND !empty($zz_sub)) {
	$zz = $zz_sub;
	unset($zz_sub);
}

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
$zz['hooks']['after_update'] = 'mod_default_password_update';

if (!empty($_GET['url'])) {
	$zz_conf['redirect']['successful_update'] = $_GET['url'];
}
$zz_conf['text']['--']['Edit a record'] = 'Change My Password';
$zz_conf['no_timeframe'] = true;
if (empty($_GET['referer'])) {
	if (is_array($zz_setting['login_entryurl'])) {
		if (!empty($_SESSION['domain']) AND !empty($zz_setting['login_entryurl'][$_SESSION['domain']]))
			$zz_conf['referer'] = $zz_setting['login_entryurl'][$_SESSION['domain']];
	} else {
		$zz_conf['referer'] = $zz_setting['login_entryurl'];
	}
}


function mod_default_password_update() {
	if (empty($_SESSION['dont_require_old_password'])) return;
	$success = wrap_session_start();
	$_SESSION['dont_require_old_password'] = false;
	session_write_close();
}

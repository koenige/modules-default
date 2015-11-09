<?php

/**
 * default module
 * Database form for own password
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


// just allow access for login table to own login ID
$zz['where']['login_id'] = $_SESSION['login_id'];
require_once $zz_conf['form_scripts'].'/logins.php';

// 2 = username
$zz['fields'][2]['title'] = 'Username';
$zz['fields'][2]['type'] = 'hidden';

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
	if (in_array($no, array(1, 2, 3, 9, 99))) {
		unset($zz['fields'][$no]);
	}
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

function mod_default_password_update() {
	if (empty($_SESSION['dont_require_old_password'])) return;
	$success = wrap_session_start();
	$_SESSION['dont_require_old_password'] = false;
	session_write_close();
}

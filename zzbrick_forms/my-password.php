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

// 3 = password
$zz['fields'][3]['type'] = 'password_change';
unset($zz['fields'][3]['function']); // no password function, e. g. random pwd

// 9 = password_change
$zz['fields'][9]['type'] = 'hidden';
$zz['fields'][9]['hide_in_form'] = true;
$zz['fields'][9]['value'] = 'no';

// 99 = last_update
$zz['fields'][99]['hide_in_form'] = true;

foreach (array_keys($zz['fields']) as $no) {
	if (in_array($no, array(1, 3, 9, 99))) continue;
	unset($zz['fields'][$no]);
}

$zz['title'] = 'Change Password';
$zz['explanation'] = markdown(
	'### '.wrap_text('Hints for secure passwords')
	."\n\n".wrap_text('password-rules')
);
$zz['access'] = 'edit_only';

if (!empty($_GET['url'])) {
	$zz_conf['redirect']['successful_update'] = $_GET['url'];
}
$zz_conf['text']['--']['Edit a record'] = 'Change My Password';

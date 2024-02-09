<?php

/**
 * default module
 * simple access rights based on logins.login_rights
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015-2016, 2020-2022, 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function brick_access_rights($parameter = []) {
	// allow parameter to be array or string
	if (!$parameter) $group = false;
	elseif (is_array($parameter)) $group = $parameter[0];
	else $group = $parameter;

	$login_rights = $_SESSION['login_rights'] ?? false;

	$group = strtolower($group);
	switch ($group) {
	case 'localhost':
		if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') return true;
		if ($_SERVER['REMOTE_ADDR'] === '::1') return true;
		if (!empty($_SERVER['SERVER_ADDR']) AND $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']) return true;
		if ($login_rights) return true; // a user is logged in
		break;
	case 'admin':
	default:
		if ($login_rights === 'admin') return true;
		break;
	case 'read and write':
		if ($login_rights === 'admin') return true;
		if ($login_rights === 'read and write') return true;
		break;
	case 'read':
		if ($login_rights === 'admin') return true;
		if ($login_rights === 'read and write') return true;
		if ($login_rights === 'read') return true;
		break;
	}
	return false;
}

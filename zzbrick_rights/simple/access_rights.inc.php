<?php

/**
 * default module
 * simple access rights based on logins.login_rights
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015-2016, 2020-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function brick_access_rights($parameter = []) {
	if (empty($_SESSION['login_rights'])) 
		return false; // no rights for this person = no access

	// allow parameter to be array or string
	if (!$parameter) $group = false;
	elseif (is_array($parameter)) $group = $parameter[0];
	else $group = $parameter;

	$group = strtolower($group);
	switch ($group) {
	case 'admin':
	default:
		if ($_SESSION['login_rights'] === 'admin') return true;
		break;
	case 'read and write':
		if ($_SESSION['login_rights'] === 'admin') return true;
		if ($_SESSION['login_rights'] === 'read and write') return true;
		break;
	case 'read':
		if ($_SESSION['login_rights'] === 'admin') return true;
		if ($_SESSION['login_rights'] === 'read and write') return true;
		if ($_SESSION['login_rights'] === 'read') return true;
		break;
	}
	return false;
}

<?php

/**
 * default module
 * simple access rights based on logins.login_rights
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015-2016, 2020-2022, 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function brick_access_rights($groups = []) {
	// allow groups parameter to be array or string
	if (!is_array($groups)) $groups = [$groups];

	$login_rights = wrap_session_value('login_rights');

	foreach ($groups as $group) {
		$group = strtolower($group);
		switch ($group) {
		case 'cron':
			if (!empty($_SERVER['REMOTE_ADDR']) AND in_array($_SERVER['REMOTE_ADDR'], wrap_setting('cron_ips'))) return true;
			if (wrap_http_localhost_ip()) return true;
			if ($login_rights === 'admin') return true;
			break;
		case 'localhost':
			if (wrap_http_localhost_ip()) return true;
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
	}
	return false;
}

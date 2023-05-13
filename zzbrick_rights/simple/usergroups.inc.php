<?php 

/**
 * default module
 * simple access registration of usergroups based on logins.login_rights
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015-2016, 2020-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function wrap_register_usergroups($user_id) {
	if (wrap_setting('login_with_email') OR wrap_setting('login_with_contact_id')) {
		$sql = 'SELECT login_rights
			FROM /*_PREFIX_*/logins
			WHERE contact_id = %d';
	} else {
		$sql = 'SELECT login_rights
			FROM /*_PREFIX_*/logins
			WHERE login_id = %d';
	}
	$sql = sprintf($sql, $user_id);
	$_SESSION['login_rights'] = wrap_db_fetch($sql, '', 'single value');
}

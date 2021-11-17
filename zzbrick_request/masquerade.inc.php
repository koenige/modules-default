<?php 

/**
 * default module
 * masquerade login to a different account
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2012, 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * masquerade login to a different account
 * 
 * @param array $params
 *		[0]: login ID
 * @return array $page 'text' for page
 */
function mod_default_masquerade($params) {
	global $zz_setting;
	if (count($params) !== 1) return false;

	if (!wrap_access('default_masquerade')) wrap_quit(403);
	wrap_session_start();
	wrap_register($params[0]);
	wrap_redirect_change($zz_setting['login_entryurl']);
}

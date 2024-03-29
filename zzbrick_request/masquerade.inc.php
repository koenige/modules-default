<?php 

/**
 * default module
 * masquerade login to a different account
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2012, 2021, 2023 Gustaf Mossakowski
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
	if (count($params) !== 1) return false;

	wrap_access_quit('default_masquerade');

	wrap_session_start();
	wrap_register($params[0]);
	wrap_redirect_change(wrap_domain_path('login_entry'));
}

<?php 

/**
 * default module
 * Database administration with adminer
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2021, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Database administration with adminer
 * set rights in webpages table
 *
 * @param $params void
 * @return array $page
 */
function mod_default_adminer($params) {
	global $zz_page;
	if ($params) return false;
	if (!wrap_access('default_adminer')) wrap_quit(403);
	if (empty($_GET)) {
		// auto-login if only one database is present
		$url = sprintf('%s?username=&db=%s'
			, $zz_page['url']['full']['path']
			, wrap_setting('db_name')
		);
		return wrap_redirect($url, 302, false);
	}
	
	$path = wrap_setting('lib').'/adminer/adminer-mysql-de.php';
	if (!file_exists($path)) {
		wrap_error('Library Adminer does not exist', E_USER_ERROR);
		exit;
	}
	session_start();
	// set a password to disable password request (from 4.7.2 on)
	$_SESSION['pwds']['server'][''][''] = 'random';
	require $path;
	exit;
}

function adminer_object() {

    class AdminerSoftware extends Adminer {
        
        function name() {
            // custom name in title and heading
			return wrap_setting('project');
        }
        
        function credentials() {
            // server, username and password for connecting to database
			$db = wrap_db_credentials();
			if (!empty($db['db_port']))
				$db['db_host'] = sprintf('%s:%s', $db['db_host'], $db['db_port']);
	        return [$db['db_host'], $db['db_user'], $db['db_pwd']];
        }
        
        function database() {
            // database name, will be escaped by Adminer
			return wrap_setting('db_name');
        }

		function login($login, $password) {
      		// validate user submitted credentials
      		// here: empty password, because adminer is behind login page already
      		return true;
    	}
    }
    
    return new AdminerSoftware;
}

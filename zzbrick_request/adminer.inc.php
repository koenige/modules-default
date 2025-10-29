<?php 

/**
 * default module
 * Database administration with adminer
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2021, 2023-2025 Gustaf Mossakowski
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
	if ($params) return false;
	wrap_access_quit('default_adminer');
	if (empty($_GET)) {
		// auto-login if only one database is present
		$url = sprintf('%s?username=&db=%s'
			, parse_url(wrap_setting('request_uri'), PHP_URL_PATH)
			, wrap_setting('db_name')
		);
		return wrap_redirect($url, 302, false);
	}
	
	$path = wrap_setting('lib').'/adminer/adminer-mysql-de.php';
	if (!file_exists($path)) {
		wrap_error('Library Adminer does not exist', E_USER_ERROR);
		exit;
	}
	// Close framework session and start Adminer's session
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_write_close();
	}
	
	// Start Adminer's session with its own name and cookie params
	@ini_set('session.use_trans_sid', '0');
	session_cache_limiter('');
	session_name('adminer_sid');
	$request_path = preg_replace('~\?.*~', '', $_SERVER['REQUEST_URI']);
	$is_https = (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') !== 0) 
		|| ini_get('session.cookie_secure');
	session_set_cookie_params(0, $request_path, '', $is_https, true);
	
	if (session_status() === PHP_SESSION_NONE) {
		ini_set('session.use_only_cookies', '1');
		session_start();
		// Set password to skip login prompt (Adminer 4.7.2+)
		$_SESSION['pwds']['server'][''][''] = 'random';
	}
	
	require $path;
	exit;
}

function adminer_object() {

    class AdminerSoftware extends Adminer\Adminer {
        
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

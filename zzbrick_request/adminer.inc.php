<?php 

/**
 * default module
 * Database administration with adminer
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2020 Gustaf Mossakowski
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
	global $zz_setting;
	global $zz_page;
	global $zz_conf;
	if ($params) return false;
	if (empty($zz_setting['adminer_databases']) AND empty($_GET)) {
		// auto-login if only one database is present
		$url = sprintf('%s?username=&db=%s'
			, $zz_page['url']['full']['path']
			, $zz_conf['db_name']
		);
		return brick_format('%%% redirect '.$url.' %%%');
	}
	
	$path = $zz_setting['lib'].'/adminer/adminer-mysql-de.php';
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
            return wrap_get_setting('project');
        }
        
        function credentials() {
            // server, username and password for connecting to database
		    global $zz_setting;
		    if (!$zz_setting['local_access']) {
			    require $zz_setting['custom'].'/zzwrap_sql/pwd.inc.php';
	        } else {
	        	require $zz_setting['local_pwd'];
	        }
	        if (!empty($db_port)) $db_host = sprintf('%s:%d', $db_host, $db_port);
	        return [$db_host, $db_user, $db_pwd];
        }
        
        function database() {
            // database name, will be escaped by Adminer
		    global $zz_conf;
            return $zz_conf['db_name'];
        }

		function login($login, $password) {
      		// validate user submitted credentials
      		// here: empty password, because adminer is behind login page already
      		return true;
    	}
    }
    
    return new AdminerSoftware;
}

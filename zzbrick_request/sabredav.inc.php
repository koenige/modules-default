<?php

/**
 * default module
 * adding sabreDAV as a WebDAV server
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014, 2020, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_lib('sabredav');

/**
 * use sabreDAV for a webDAV server
 * use CMS authentication
 * sabreDAV only supports macOS Finder with mod_php!
 *
 * currently need to set setting 'dav_url', too
 * @param void
 * @return void
 */
function mod_default_sabredav() {
	global $zz_page;

	$webdav_path = wrap_setting('cms_dir').'/webdav/';
	$base_uri = $zz_page['db']['identifier'];
	$base_uri = rtrim($base_uri, '*');

	// Now we're creating a whole bunch of objects
	$rootDirectory = new \Sabre\DAV\FS\Directory($webdav_path.'public');

	// The server object is responsible for making sense out of the WebDAV protocol
	$server = new \Sabre\DAV\Server($rootDirectory);

	// If your server is not on your webroot, make sure the following line has the
	// correct information
	$server->setBaseUri($base_uri);

	// The lock manager is reponsible for making sure users don't overwrite
	// each others changes.
	$lockBackend = new \Sabre\DAV\Locks\Backend\File($webdav_path.'data/locks');
	$lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
	$server->addPlugin($lockPlugin);

	// This ensures that we get a pretty index in the browser, but it is
	// optional.
	$server->addPlugin(new \Sabre\DAV\Browser\Plugin());

	// Automatically guess (some) contenttypes, based on extesion
	$server->addPlugin(new \Sabre\DAV\Browser\GuessContentType());

	$authBackend = new BasicCallBack();
	$auth = new \Sabre\DAV\Auth\Plugin($authBackend, wrap_setting('site'));
	$server->addPlugin($auth);

	// Temporary file filter
	wrap_mkdir(wrap_setting('tmp_dir').'/webdav');
	$tffp = new \Sabre\DAV\TemporaryFileFilterPlugin(wrap_setting('tmp_dir').'/webdav');
	$server->addPlugin($tffp);

	// All we need to do now, is to fire up the server
	$server->exec();
	exit;
}

class BasicCallBack extends \Sabre\DAV\Auth\Backend\AbstractBasic {

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected function validateUserPass($username, $password) {
    	$login['username'] = $username;
    	$login['password'] = $password;
		wrap_sql('auth', 'set');
		wrap_session_start();
    	$success = wrap_login($login);
    	return $success;
    }
}

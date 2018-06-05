<?php

/**
 * default module
 * Cleanup script
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2018 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_cleanup() {
	global $zz_setting;
	
	// Sessions
	$data['session_cleanup'] = mod_default_session_cleanup();
	$page['text'] = wrap_template('cleanup', $data);
	return $page;
}

/**
 * deletion of session cookie fiels if someone did not log out properly after
 * the maximum lifetime of a session
 *
 * @return int
 */
function mod_default_session_cleanup() {
	$counter = 0;
	global $zz_setting;
	if (empty($zz_setting['session_save_path'])) return $counter;
	if (!is_dir($zz_setting['session_save_path'])) return $counter;
	$files = scandir($zz_setting['session_save_path']);
	if (!$files) return $counter;
	$invalid = time() - $zz_setting['logout_inactive_after'] * 60;
	foreach ($files as $file) {
		if (substr($file, 0, 1) === '.') continue;
		$filename = $zz_setting['session_save_path'].'/'.$file;
		if (filemtime($filename) < $invalid) {
			unlink($filename);
			$counter++;
		}
	}
	return $counter;
}

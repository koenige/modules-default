<?php 

/**
 * default module
 * common functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get path to masquerade login
 *
 * @param array $values
 *		int user_id (or whatever it is called, first parameter)
 * @return string
 */
function mf_default_masquerade_path($values) {
	global $zz_setting;
	if (!wrap_access('default_masquerade')) return false;
	if (empty($zz_setting['default_masquerade_path'])) {
		$success = wrap_setting_path('default_masquerade_path');
		if (!$success) return false;
	}
	return sprintf($zz_setting['base'].$zz_setting['default_masquerade_path'], reset($values));
}

/**
 * get all used logfiles with standard error log format
 *
 * @return array
 */
function mf_default_logfiles() {
	global $zz_conf;
	global $zz_setting;

	$logfiles = [];
	// PHP logfile?
	if ($php_log = ini_get('error_log')) {
		$php_log = realpath($php_log);
		$logfiles[$php_log]['path'] = $php_log;
		$logfiles[$php_log]['title'][] = 'PHP';
	}
	// zzform, zzwrap logfiles?
	$levels = ['error', 'warning', 'notice'];
	foreach ($levels as $level) {
		if ($zz_conf['error_log'][$level]) {
			$logfile = realpath($zz_conf['error_log'][$level]);
			if (!$logfile) continue;
			$logfiles[$logfile]['path'] = $logfile;
			$logfiles[$logfile]['log'] = basename($logfile);
			$logfiles[$logfile]['title'][] = ucfirst($level);
		}
	}
	return $logfiles;
}

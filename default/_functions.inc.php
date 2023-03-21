<?php 

/**
 * default module
 * common functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get all used logfiles with standard error log format
 *
 * @return array
 */
function mf_default_logfiles() {
	global $zz_conf;

	$logfiles = [];
	// PHP logfile?
	if ($php_log = ini_get('error_log')) {
		$php_log = realpath($php_log);
		$logfiles[$php_log]['path'] = $php_log;
		$logfiles[$php_log]['title'][] = 'PHP';
		$logfiles[$php_log]['types'][] = 'PHP';
	}
	// zzform, zzwrap logfiles?
	$levels = ['error', 'warning', 'notice'];
	foreach ($levels as $level) {
		if ($logfile = wrap_setting('error_log['.$level.']')) {
			$logfile = realpath($logfile);
			if (!$logfile) continue;
			$logfiles[$logfile]['path'] = $logfile;
			$logfiles[$logfile]['log'] = basename($logfile);
			$logfiles[$logfile]['title'][] = ucfirst($level);
			$logfiles[$logfile]['types'][] = 'zzform';
			$logfiles[$logfile]['types'][] = 'zzwrap';
		}
	}
	// module logfiles, extra logfiles?
	$extra_logfiles = array_merge(wrap_setting('modules'), wrap_setting('logfiles_custom'));
	foreach ($extra_logfiles as $log) {
		$logfile = sprintf('%s/%s.log', wrap_setting('log_dir'), $log);
		if (file_exists($logfile)) {
			$logfiles[$logfile]['path'] = $logfile;
			$logfiles[$logfile]['log'] = sprintf('%s.log', $log);
			$logfiles[$logfile]['title'][] = ucfirst($log);
			$logfiles[$logfile]['types'][] = $log;
		}
	}
	
	// make types unique
	foreach (array_keys($logfiles) as $logfile) {
		$logfiles[$logfile]['types'] = array_unique($logfiles[$logfile]['types']);
	}
	
	return $logfiles;
}

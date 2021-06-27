<?php

/**
 * default module
 * show overview of error logs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show settings for error and further logging
 *
 * @return array
 */
function mod_default_errorlogs() {
	global $zz_conf;

	if (!empty($zz_conf['error_handling'])) {
		$data['error_handling_'.$zz_conf['error_handling']] = true;
	}
	$data['error_mail_level'] = is_array($zz_conf['error_mail_level']) ? implode(', ', $zz_conf['error_mail_level']) : $zz_conf['error_mail_level'];
	
	if ($zz_conf['log_errors']) {
		$data['logfiles'] = [];
		if ($php_log = ini_get('error_log')) {
			$php_log = realpath($php_log);
			$data['logfiles'][$php_log]['path'] = $php_log;
			$data['logfiles'][$php_log]['title'][] = 'PHP';
		}
		$levels = ['error', 'warning', 'notice'];
		foreach ($levels as $level) {
			if ($zz_conf['error_log'][$level]) {
				$logfile = realpath($zz_conf['error_log'][$level]);
				if (!$logfile) continue;
				$data['logfiles'][$logfile]['path'] = $logfile;
				$data['logfiles'][$logfile]['title'][] = ucfirst($level);
			}
		}
		$data['logfiles'] = array_values($data['logfiles']);
		foreach ($data['logfiles'] as $index => $logfile) {
			$data['logfiles'][$index]['title'] = implode(', ', $logfile['title']);
		}
	}

	$page['text'] = wrap_template('errorlogs', $data);
	return $page;
}

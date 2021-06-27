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
	global $zz_setting;

	$lines[0]['th'] = wrap_text('Error handling');
	$lines[0]['td'] = (!empty($zz_conf['error_handling']) ? $zz_conf['error_handling'] : '');
	$lines[0]['explanation']['output'] = wrap_text('Errors will be shown on webpage');
	$lines[0]['explanation']['mail'] = wrap_text('Errors will be sent via mail');
	$lines[0]['explanation'][false] = wrap_text('Errors won’t be shown');

	$lines[1] = [
		'th' => wrap_text('Send mail for these error levels'),
		'td' => (is_array($zz_conf['error_mail_level']) ? implode(', ', $zz_conf['error_mail_level']) : $zz_conf['error_mail_level'])
	];
	$lines[3] = [
		'th' => wrap_text('Send mail (From:)'),
		'td' => (!empty($zz_conf['error_mail_from']) ? $zz_conf['error_mail_from'] : ''),
		'explanation' => [false => wrap_text('not set')],
		'class' => 'level1'
	];
	$lines[5] = [
		'th' => wrap_text('Send mail (To:)'),
		'td' => (!empty($zz_conf['error_mail_to']) ? $zz_conf['error_mail_to'] : ''),
		'explanation' => [false => wrap_text('not set')],
		'class' => 'level1'
	];

	$lines[6]['th'] = wrap_text('Logging');
	$lines[6]['td'] = $zz_conf['log_errors'];
	$lines[6]['explanation'][1] = wrap_text('Errors will be logged');
	$lines[6]['explanation'][false] = wrap_text('Errors will not be logged');

	if ($zz_conf['log_errors']) {

		// get logfiles
		$logfiles = [];
		if ($php_log = ini_get('error_log'))
			$logfiles[realpath($php_log)][] = 'PHP';
		$levels = ['error', 'warning', 'notice'];
		foreach ($levels as $level) {
			if ($zz_conf['error_log'][$level]) {
				$logfile = realpath($zz_conf['error_log'][$level]);
				if (!$logfile) continue;
				$logfiles[$logfile][] = ucfirst($level);
			}
		}
		$no = 8;
		foreach ($logfiles as $file => $my_levels) {
			$lines[$no] = [
				'th' => sprintf(wrap_text('Logfile for %s'), '<strong>'
				.implode(', ' , $my_levels).'</strong>'),
				'td' => '<a href="?log='.urlencode($file)
				.'&amp;filter[type]=none">'.$file.'</a>',
				'class' => 'level1'
			];
			$no = $no +2;
		}

		$lines[20]['th'] = wrap_text('Maximum length of single error log entry');
		$lines[20]['td'] = $zz_conf['log_errors_max_len'];
		$lines[20]['class'] = 'level1';
	
		$lines[22]['th'] = wrap_text('Log POST variables when errors occur');
		$lines[22]['td'] = (!empty($zz_conf['error_log_post']) ? $zz_conf['error_log_post'] : false);
		$lines[22]['explanation'][1] = wrap_text('POST variables will be logged');
		$lines[22]['explanation'][false] = wrap_text('POST variables will not be logged');
		$lines[22]['class'] = 'level1';

	}

	$lines[23]['th'] = wrap_text('Logging (Upload)');
	$lines[23]['td'] = !empty($zz_conf['upload_log']) ? '<a href="?log='.urlencode($zz_setting['log_dir'].'/upload.log')
				.'">'.$zz_setting['log_dir'].'/upload.log</a>' : wrap_text('disabled');

	$lines[24]['th'] = wrap_text('Logging (Mail)');
	$lines[24]['td'] = !empty($zz_setting['log_mail']) ? '<a href="?maillog">'.$zz_setting['log_dir'].'/mail.log</a>' : wrap_text('disabled');

	foreach ($lines as $index => $line) {
		if (!$line['td']) $line['td'] = false;
		$lines[$index]['class'] = !empty($line['class']) ? $line['class'].' block480a' : 'block480';
		$lines[$index]['td_class'] = $index & 1 ? 'uneven' : 'even';
		$lines[$index]['explanation'] = !empty($line['explanation'][$line['td']]) ? $line['explanation'][$line['td']] : false;
	}

	$page['text'] = wrap_template('errorlogs', $lines);
	return $page;
}

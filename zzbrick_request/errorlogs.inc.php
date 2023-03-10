<?php

/**
 * default module
 * show overview of error logs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show settings for error and further logging
 *
 * @return array
 */
function mod_default_errorlogs() {
	if (wrap_setting('error_handling')) {
		$data['error_handling_'.wrap_setting('error_handling')] = true;
	}
	$data['error_mail_level'] = implode(', ', wrap_setting('error_mail_level'));
	
	if (wrap_setting('log_errors')) {
		$data['logfiles'] = mf_default_logfiles();
		$data['logfiles'] = array_values($data['logfiles']);
		foreach ($data['logfiles'] as $index => $logfile) {
			$data['logfiles'][$index]['title'] = implode(', ', $logfile['title']);
			$data['logfiles'][$index]['filesize'] = filesize($logfile['path']);
			if (str_starts_with($logfile['path'], wrap_setting('cms_dir')))
				$data['logfiles'][$index]['inside_log_dir'] = true;
			if (count($data['logfiles'][$index]['types']) === 1)
				$data['logfiles'][$index]['types'] = false;
		}
	}

	if (wrap_setting('log_mail'))) {
		$data['mail_log_filesize'] = file_exists(wrap_setting('log_dir').'/mail.log')
			? filesize(wrap_setting('log_dir').'/mail.log') : 0;		
	}
	if (wrap_setting('zzform_upload_log')) {
		$data['upload_log_filesize'] = file_exists(wrap_setting('log_dir').'/upload.log')
			? filesize(wrap_setting('log_dir').'/upload.log') : 0;		
	}

	$page['text'] = wrap_template('errorlogs', $data);
	return $page;
}

<?php 

/**
 * default module
 * output of mail log
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * output of mail log
 *
 * @param array $params
 * @return array $page
 */
function mod_default_make_maillog($params) {
	$page['title'] = wrap_text('Mail Log');
	$page['breadcrumbs'][]['title'] = wrap_text('Mail Log');
	$page['query_strings'] = ['limit', 'mail_sent'];
	$page['extra']['css'][] = 'default/maintenance.css';
	$logfile = wrap_setting('log_dir').'/mail.log';
	if (!file_exists($logfile)) {
		$data = [
			'logfile' => $logfile,
			'logfile_inexistent' => true
		];
		$page['text'] = wrap_template('maillog', $data);
		return $page;
	}

	zzform_list_init();
	$data = [];

	if (!empty($_POST['line'])) {
		wrap_include('file', 'zzwrap');
		$data['message'] = wrap_file_delete_line($logfile, $_POST['line']);
	} elseif (!empty($_POST['resend'])) {
		$resend = array_keys($_POST['resend']);
		$resend = reset($resend);
	}
	if (!empty($_GET['mail_sent']))
		$data['message'] = wrap_text('The mail was sent again.');

	// get no. of mails
	$j = 0;
	$data['mails'] = [];
	$mail_no = 0;
	$data['mails'][$mail_no]['m_start'] = 0;
	$data['mails'][$mail_no]['m_no'] = 0;
	$separator = trim(wrap_mail_separator());
	$file = new \SplFileObject($logfile, 'r');
	$mail_end = false;
	while (!$file->eof()) {
		$line = $file->fgets();
		$line = trim($line);
		if ($mail_end) {
			if ($line) {
				$mail_no++;
				$data['mails'][$mail_no]['m_start'] = $j;
				$data['mails'][$mail_no]['m_no'] = $mail_no;
				$mail_end = false;
			}
		}
		if ($line === $separator) {
			$data['mails'][$mail_no]['m_end'] = $j + 1;
			$mail_end = true;
		}
		$j++;
	}
	$data['mails'][$mail_no]['m_end'] = $j - 1;
	
	if (!empty($resend) AND !empty($data['mails'][$resend])) {
		$first = $resend;
		$last = $resend;
	} else {
		// check limits
		list($first, $last) = mod_default_make_maillog_limit(count($data['mails']));
	}
	
	$display = [];
	for ($i = $first; $i <= $last; $i++) {
		if (empty($data['mails'][$i])) break;
		$current = $data['mails'][$i]['m_start'];
		$file->seek($current);
		$data['mails'][$i]['m_raw_content'] = [];
		while($current < $data['mails'][$i]['m_end']) {
			$line = trim($file->current());
			if ($line OR $data['mails'][$i]['m_raw_content']) {
				$data['mails'][$i]['m_raw_content'][] = $line;
			}
			$current++;
			$file->next();
		}
		$mail_head = true;
		foreach ($data['mails'][$i]['m_raw_content'] as $index => $line) {
			if ($mail_head) {
				if (!$line)	{
					$mail_head = false;
					continue;
				}
				$key = substr($line, 0, strpos($line, ':'));
				$value = substr($line, strpos($line, ':') + 2);
			} elseif (trim($line) !== $separator) {
				$key = 'm_msg';
				$value = $line;
				if (strstr($value, '%%%'))
					$value = str_replace('%%%', '%/%/%', $value);
			}
			if (array_key_exists($key, $data['mails'][$i])) {
				$data['mails'][$i][$key] .= "\n".$value;
			} else {
				$data['mails'][$i][$key] = $value;
			}
		}
		$display[] = $i;
	}

	$data['total_rows'] = count($data['mails']);
	foreach (array_keys($data['mails']) as $index) {
		if (in_array($index, $display)) continue;
		unset($data['mails'][$index]);
	}
	if (!empty($resend) AND count($data['mails']) === 1) {
		$maildata = reset($data['mails']);
		$mail = [];
		$mail['to'] = $maildata['To'];
		$mail['subject'] = $maildata['Subject'];
		$mail['message'] = $maildata['m_msg'];
		foreach ($maildata as $key => $value) {
			if (in_array($key, ['To', 'Subject'])) continue;
			if (substr($key, 0, 2) === 'm_') continue;
			$mail['headers'][$key] = $value;
		}
		// no signature, no prefix, was already added
		wrap_setting('mail_with_signature', false);
		wrap_setting('mail_subject_prefix', '');
		$success = wrap_mail($mail);
		if (!$success) $data['message'] = wrap_text('Mail was not sent.');
		wrap_redirect_change(wrap_setting('request_uri').'&mail_sent=1');
	}
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($data['total_rows']);

	$page['text'] = wrap_template('maillog', $data);
	$page['text'] .= wrap_template('zzform-foot');
	return $page;
}

/**
 * get first and last mail to display in list
 *
 * @return array
 */
function mod_default_make_maillog_limit($total_rows) {
	if (!empty($_GET['limit']) AND $_GET['limit'] === 'last') {
		wrap_page_limit('last', $total_rows); // not + 1 since logs always end with a newline
	}
	$first = wrap_page_limit('start');
	$last = wrap_page_limit('end');
	return [$first, $last];
}

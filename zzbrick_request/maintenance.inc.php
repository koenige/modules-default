<?php 

/**
 * default module
 * Maintenance script for database operations with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Maintenance script for zzform to do some cleanup/correction operations
 *
 * - change database name if local development uses different database names
 * for relations and translations
 * - delete files from backup-directory
 * - enter an sql query
 * @param array $params
 * @global array $zz_conf configuration variables
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_maintenance($params) {
	wrap_access_quit('default_maintenance');

	wrap_setting('dont_show_h1', false); // internal, no need to hide it
	wrap_setting_add('extra_http_headers', 'X-Frame-Options: Deny');
	wrap_setting_add('extra_http_headers', "Content-Security-Policy: frame-ancestors 'self'");

	if (isset($brick['page'])) $page = $brick['page'];
	$page['head'] = isset($page['head']) ? $page['head'] : '';
	if (wrap_setting('zzform_no_packagecss'))
		$page['head'] .= wrap_template('zzform-head');
	else
		wrap_package_activate('zzform'); // for CSS
	$page['title'] = wrap_setting('zzform_heading_prefix') ? wrap_text(wrap_setting('zzform_heading_prefix')) : '';
	$page['extra']['css'][] = 'default/maintenance.css';
	if (!empty($_GET) OR !empty($_POST)) {
		$page['title'] .= ' <a href="./">'.wrap_text('Maintenance').'</a>:';
		$page['breadcrumbs'][] = '<a href="./">'.wrap_text('Maintenance').'</a>';
	}

	if (!empty($_GET['folder'])) {
		wrap_include('zzbrick_make/filetree', 'default');
		return zz_maintenance_folders($page);
	} elseif (isset($_GET['maillog'])) {
		return zz_maintenance_maillogs($page);
	} elseif (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif ($type = zz_maintenance_keycheck()) {
		$brick = '%%% '.$type['verb'].' '.$type['key'].' '.($_GET[$type['key']] ?? '').' %%%';
		$newpage = brick_format($brick);
		if ($newpage['status'] === 404) return $newpage;
		if (isset($newpage['content_type']) AND $newpage['content_type'] !== 'html') return $newpage;
		$page['title'] .= ' '.$newpage['title'];
		$page['text'] = $newpage['text'];
		$page['extra'] = array_merge_recursive($page['extra'], $newpage['extra'] ?? []);
		$page['breadcrumbs'] = array_merge($page['breadcrumbs'], $newpage['breadcrumbs']);
		if (!empty($newpage['query_strings']))
			$page['query_strings'] = $newpage['query_strings'];
		if (!empty($newpage['head']))
			$page['head'] .= $newpage['head'];
		$page['query_strings'][] = $type['key'];
		return $page;
	}

	$data = [];
	$data = array_merge($data, zz_maintenance_tables());
	$data['php_version'] = phpversion();
	wrap_include('upload', 'zzform');
	$functions = ['convert', 'gs', 'exiftool', 'file'];
	// @todo check why 'pdfinfo' does not return anything
	foreach ($functions as $function) {
		$full = zz_upload_binary_version($function, false);
		$data[$function] = explode("\n", $full);
		if ($function === 'convert') {
			$data['convert'] = str_replace('Version: ', '', $data['convert']);
			$data['convert'] = str_replace('https://imagemagick.org', '', $data['convert']);
		}
		$data[$function] = $data[$function][0];
	}
	$data['mysql'] = mysqli_get_server_info(wrap_db_connection());
	wrap_include('zzbrick_make/filetree', 'default');
	$folders = zz_maintenance_folders();
	$data['folders'] = $folders['text'];

	$page['text'] = wrap_template('maintenance', $data);
	$page['title'] .= ' '.wrap_text('Maintenance');
	$page['breadcrumbs'][]['title'] = wrap_text('Maintenance');
	return $page;
}

/**
 * check script depending on GET variable
 *
 * @return string
 */
function zz_maintenance_keycheck() {
	$keys = [
		'cachedircheck' => 'make',
		'dbmodules' => 'make',
		'dbupdate' => 'make',
		'filetree' => 'make',
		'integritycheck' => 'request',
		'log' => 'make',
		'loggingadd' => 'make',
		'loggingread' => 'request',
		'serversync_development' => 'make',
		'sqlquery' => 'make',
		'toolinfo' => 'request',
		'translationscheck' => 'make'
	];
	foreach ($keys as $key => $verb) {
		if (isset($_GET[$key])) return ['key' => $key, 'verb' => $verb];
		if (isset($_POST[$key])) return ['key' => $key, 'verb' => $verb];
	}
	return '';
}

/**
 * list and modify databases for translation and relation tables
 *
 * @return array
 */
function zz_maintenance_tables() {
	$data = [];

	if (!wrap_setting('zzform_check_referential_integrity') AND !wrap_setting('translate_fields'))
		return $data;
		
	// Update
	if ($_POST AND !empty($_POST['db_value'])) {
		$areas = ['master', 'detail', 'translation'];
		foreach ($areas as $area) {
			if (!empty($_POST['db_value'][$area])) {
				foreach ($_POST['db_value'][$area] as $old => $new) {
					if (empty($_POST['db_set'][$area][$old])) continue;
					if ($_POST['db_set'][$area][$old] != 'change') continue;
					if ($area === 'translation') {
						$table = wrap_sql_table('default_translationfields');
						$field_name = 'db_name';
					} else {
						$table = wrap_sql_table('zzform_relations');
						$field_name = $area.'_db';
					}
					$sql = 'UPDATE %s SET %s = "%s" WHERE %s = "%s"';
					$sql = sprintf($sql, $table,
						$field_name, wrap_db_escape($new),
						$field_name, wrap_db_escape($old)
					);
					wrap_db_query($sql);
				}
			}
		}
		wrap_redirect_change();
	}
	if (wrap_setting('zzform_check_referential_integrity')) {
	// Master database
		$sql = 'SELECT DISTINCT master_db FROM %s';
		$sql = sprintf($sql, wrap_sql_table('zzform_relations'));
		$dbs['master'] = wrap_db_fetch($sql, 'master_db', 'single value');

	// Detail database	
		$sql = 'SELECT DISTINCT detail_db FROM %s';
		$sql = sprintf($sql, wrap_sql_table('zzform_relations'));
		$dbs['detail'] = wrap_db_fetch($sql, 'detail_db', 'single value');
	}

	if (wrap_setting('translate_fields')) {
	// Translations database	
		$sql = 'SELECT DISTINCT db_name FROM %s';
		$sql = sprintf($sql, wrap_sql_table('default_translationfields'));
		$dbs['translation'] = wrap_db_fetch($sql, 'db_name', 'single value');
	}
	
	// All available databases
	$sql = 'SHOW DATABASES';
	$databases = wrap_db_fetch($sql, 'Databases', 'single value');
	foreach ($databases as $db) {
		// no system databases
		if (in_array($db, ['information_schema'])) continue;
		$db_list[] = [
			'db' => $db,
			'prefered' => $db === wrap_setting('db_name') ? true : false
		];
	}
	$data['database_changeable'] = false;
	if (count($db_list) > 1) {
		$data['database_changeable'] = true;
	} else {
		foreach ($dbs as $db) {
			if (reset($db) === wrap_setting('db_name')) continue;
			$data['database_changeable'] = true;
			break;
		}
	}
		
	$i = 0;
	foreach ($dbs as $category => $db_names) {
		foreach ($db_names as $db) {
			$data['tables'][] = [
				'title' => wrap_text(ucfirst($category)),
				'db' => $db,
				'category' => $category,
				'keep' => in_array($db, $databases) ? true : false,
				'databases' => $data['database_changeable'] ? $db_list : []
			];
		}
	}
	return $data;
}

/**
 * reformats SQL query for better readability
 * 
 * @param string $sql
 * @return string $sql, formatted
 */
function zz_maintenance_sql($sql) {
	$sql = preg_replace("/\s+/", " ", $sql);
	$tokens = explode(' ', $sql);
	$sql = [];
	$keywords = [
		'INSERT', 'INTO', 'DELETE', 'FROM', 'UPDATE', 'SELECT', 'UNION',
		'WHERE', 'GROUP', 'BY', 'ORDER', 'DISTINCT', 'LEFT', 'JOIN', 'RIGHT',
		'INNER', 'NATURAL', 'USING', 'SET', 'CONCAT', 'SUBSTRING_INDEX',
		'VALUES', 'CREATE', 'TABLE', 'KEY', 'CHARACTER', 'DEFAULT', 'NOT',
		'NULL', 'AUTO_INCREMENT', 'COLLATE', 'PRIMARY', 'UNIQUE', 'CHANGE',
		'RENAME'
	];
	$newline = [
		'LEFT', 'FROM', 'GROUP', 'WHERE', 'SET', 'VALUES', 'SELECT', 'CHANGE',
		'RENAME'
	];
	$newline_tab = ['ON', 'AND'];
	foreach ($tokens as $token) {
		$out = wrap_html_escape($token);
		if (in_array($token, $keywords)) $out = '<strong>'.$out.'</strong>';
		if (in_array($token, $newline)) $out = "\n".$out;
		if (in_array($token, $newline_tab)) $out = "\n\t".$out;
		$sql[] = $out;
	}
	$replace = ['%%%' => '%&shy;%%'];
	foreach ($replace as $old => $new) {
		$sql = str_replace($old, $new, $sql);
	}
	$sql = implode(' ', $sql);
	return $sql;
}

/**
 * format a single line from log
 *
 * @param string $line
 * @param array $types (optional)
 * @return array
 */
function zz_maintenance_logs_line($line, $types = []) {
	zzform_list_init();
	
	$out = [
		'type' => '',
		'user' => '',
		'date' => '',
		'level' => '',
		'time' => '',
		'status' => false
	];

	$line = trim($line);
	if (!$line) return [];

	// get date
	if (substr($line, 0, 1) === '[' AND $rightborder = strpos($line, ']')) {
		$out['date'] = substr($line, 1, $rightborder - 1);
		$line = substr($line, $rightborder + 2);
	}
	// get user
	if (substr($line, -1) === ']' AND strstr($line, '[')) {
		$out['user'] = substr($line, strrpos($line, '[')+1, -1);
		$out['user'] = explode(' ', $out['user']);
		if (count($out['user']) > 1 AND substr($out['user'][0], -1) === ':') {
			array_shift($out['user']); // get rid of User: or translations of it
		}
		$out['user'] = implode(' ', $out['user']);
		$line = substr($line, 0, strrpos($line, '['));
	}

	$tokens = explode(' ', $line);
	if ($tokens AND in_array($tokens[0], $types)) {
		$out['type'] = array_shift($tokens);
		$out['level'] = array_shift($tokens);
		if (substr($out['level'], -1) === ':') $out['level'] = substr($out['level'], 0, -1);
		else $out['level'] .= ' '.array_shift($tokens);
		if (substr($out['level'], -1) === ':') $out['level'] = substr($out['level'], 0, -1);
		$out['level_class'] = wrap_filename(strtolower($out['level']));
	}

	if (in_array($out['type'], ['zzform', 'zzwrap'])) {
		if (!$out['user'])
			$out['user'] = array_pop($tokens);
		$time = '';
		while (!$time) {
			// ignore empty tokens
			$time = trim(end($tokens));
			if (!$time) array_pop($tokens);
			if (!$tokens) break;
		}
		if (substr($time, 0, 1) === '{'
			AND substr($time, -1) === '}'
			AND is_numeric(substr($time, 1, -1))
		) {
			array_pop($tokens);
			$out['time'] = substr($time, 1, -1);
			// shorten time to make it more readable
			$out['time'] = substr($out['time'], 0, 6);
		}
	}

	if ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[0], -1) === ']') {
		$out['link'] = array_shift($tokens);
		$out['link'] = substr($out['link'], 1, -1);
		if (intval($out['link'])."" === $out['link']) {
			// e. g. 404 has no link repeated as it's already in the
			// error message	
			$out['status'] = $out['link'];
			$out['link'] = false;
		}
	} elseif ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[1], -1) === ']'
		AND strlen($tokens[0]) === 4) {
		$out['status'] = array_shift($tokens);
		$out['status'] = substr($out['status'], 1);
		$out['link'] = array_shift($tokens);
		$out['link'] = substr($out['link'], 0, -1);
	} else {
		$out['link'] = false;
	}
	$out['error'] = implode(' ', $tokens);

	$post = false;
	if (substr($out['error'], 0, 11) === 'POST[json] ') {
		$post = @json_decode(substr($out['error'], 11));
		if ($post)
			$out['error'] = 'POST '.wrap_print($post);
	}
	if (!$post) {
		$no_html = false;
		if (in_array($out['type'], ['zzform', 'zzwrap']))
			$no_html = true;
		$out['error'] = zz_maintenance_splits($out['error'], $no_html);
	}
	// htmlify links
	if (stristr($out['error'], 'http:/<wbr>/<wbr>') OR stristr($out['error'], 'https:/<wbr>/<wbr>')) {
		$out['error'] = preg_replace_callback('~(\S+):/<wbr>/<wbr>(\S+)~', 'zz_maintenance_make_url', $out['error']);
	}
	$out['error'] = str_replace(',', ', ', $out['error']);
	$out['error'] = zz_list_word_split($out['error']);
	$out['error'] = zz_mark_search_string($out['error']);
	$out['error'] = str_replace('%%%', '\%\%\%', $out['error']);

	$out['date_begin'] = $out['date'];
	$out['links'] = ($out['link'] ? '[<a href="'.str_replace('&', '&amp;', $out['link']).'">'
			.zz_maintenance_splits($out['link'], true).'</a>]<br>' : '');
	return $out;
}

/**
 * output of mail log
 *
 * @global array $zz_conf
 * @return string HTML output
 */
function zz_maintenance_maillogs($page) {
	global $zz_conf;
	wrap_include('file', 'zzwrap');

	zzform_list_init();

	$page['title'] .= ' '.wrap_text('Mail Logs');
	$page['breadcrumbs'][]['title'] = wrap_text('Mail Logs');
	$page['query_strings'] = [
		'maillog', 'limit', 'mail_sent'
	];
	$logfile = wrap_setting('log_dir').'/mail.log';
	if (!file_exists($logfile)) {
		$page['text'] = '<p>'.wrap_text('Logfile does not exist: %s'
			, ['values' => wrap_html_escape($logfile)]
		).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	$data = [];

	if (!empty($_POST['line'])) {
		$data['message'] = wrap_file_delete_line($logfile, $_POST['line']);
	} elseif (!empty($_POST['resend'])) {
		$resend = array_keys($_POST['resend']);
		$resend = reset($resend);
	}
	if (!empty($_GET['mail_sent']))
		$data['message'] = wrap_text('Mail was re-sent.');

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
		list($first, $last) = zz_maintenance_maillogs_limit(count($data['mails']));
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
		return wrap_redirect_change(wrap_setting('request_uri').'&mail_sent=1');
	}
	$data['url_self'] = wrap_html_escape(wrap_setting('request_uri'));
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($zz_conf['int']['this_limit'], $data['total_rows']);

	$page['text'] = wrap_template('maintenance-maillogs', $data);
	$page['text'] .= wrap_template('zzform-foot');
	return $page;
}

/**
 * get first and last mail to display in list
 *
 * @return array
 */
function zz_maintenance_maillogs_limit($total_rows) {
	global $zz_conf;
	if (!empty($_GET['limit']) AND $_GET['limit'] === 'last') {
		zz_list_limit_last($total_rows); // not + 1 since logs always end with a newline
	}
	$first = $zz_conf['int']['this_limit'] - wrap_setting('zzform_limit');
	$last = $zz_conf['int']['this_limit'] - 1;
	return [$first, $last];
}

/**
 * get rid of long lines with zero width space (<wbr>) - &shy; does
 * not work at least in firefox 3.6 with slashes
 *
 * @param string $string
 * @param bool $no_html
 * @return string
 */
function zz_maintenance_splits($string, $no_html) {
	if ($no_html) {
		$string = str_replace('<', '&lt;', $string);
	}
	$string = str_replace(';', ';<wbr>', $string);
	$string = str_replace('&', '<wbr>&amp;', $string);
	$string = str_replace('&amp;#8203;', '<wbr>', $string);
	$string = str_replace('/', '/<wbr>', $string);
	$string = str_replace('=', '=<wbr>', $string);
	$string = str_replace('%', '<wbr>%', $string);
	$string = str_replace('-at-', '<wbr>-at-', $string);
	return $string;
}

function zz_maintenance_make_url($array) {
	$href = str_replace('<wbr>', '', $array[0]);
	$linktext = $array[0];
	$link = '<a href="'.$href.'">'.$linktext.'</a>'; 
	return $link;
}

/**
 * put one liners or error message in standard div
 * only for non-template HTML pages
 *
 * @param array $page
 * @return array
 */
function mod_default_maintenance_return($page) {
	$page['text'] = sprintf('<div id="zzform" class="maintenance">%s</div>', $page['text']);
	return $page;
}

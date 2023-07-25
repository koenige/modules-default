<?php 

/**
 * default module
 * read and edit logfiles
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * output of logfile per line or grouped with the possibility to delete lines
 *
 * @param array $params
 * @global array $zz_conf
 * @return array $page
 */
function mod_default_make_log($params) {
	global $zz_conf;
	require_once wrap_setting('core').'/file.inc.php';

	zz_maintenance_list_init();

	$page['title'] = wrap_text('Logs');
	$page['breadcrumbs'][]['title'] = wrap_text('Logs');
	$page['query_strings'] = [
		'filter', 'log', 'limit', 'q', 'scope', 'deleteall'
	];

	$levels = ['error', 'warning', 'notice'];
	if (empty($_GET['log'])) {
		$data['no_logfile_specified'] = true;
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	$logfile = realpath($_GET['log']);
	if (!$logfile) {
		$data['logfile_does_not_exist'] = wrap_html_escape($_GET['log']);
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	$data['log'] = wrap_html_escape($logfile);
	$logfiles = mf_default_logfiles();
	$show_log = array_key_exists($logfile, $logfiles) ? true : false;
	if (!$show_log) {
		$data['logfile_not_in_use'] = wrap_html_escape($_GET['log']);
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	// delete
	$data['message'] = false;
	if (!empty($_POST['line'])) {
		$data['message'] = wrap_file_delete_line($logfile, $_POST['line']);
	}

	$filters['type'] = $logfiles[$logfile]['types'];
	$filters['level'] = [
		'Notice', 'Deprecated', 'Warning', 'Error', 'Parse error',
		'Strict error', 'Fatal error', 'Upload'
	];
	$filters['group'] = ['Group entries'];

	$data['filter'] = mod_default_make_log_filter($filters);

	if (!empty($_GET['filter']) AND !empty($_GET['filter']['type'])
		AND $_GET['filter']['type'] === 'none') {
		$data['choose_filter'] = true;
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	if (!empty($_GET['filter']) AND !empty($_GET['filter']['group'])
		AND $_GET['filter']['group'] === 'Group entries') {
		$data['group'] = true;	
		$output = [];
	} else {
		$data['group'] = false;
	}

	list($data['deleteall_url'], $data['deleteall_filter']) = zz_maintenance_deleteall_form();
	if ($data['deleteall_url']) {
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	$file = new \SplFileObject($logfile, 'r');
	if (!empty($_GET['q']) OR !empty($_GET['filter'])) {
		$found = [];
		while (!$file->eof()) {
			$line = $file->fgets();
			$line_key = $file->key() - 1;
			// fgets moves on to next key, but not in older PHP versions
			if (version_compare(PHP_VERSION, '8.0.18', '<=')
			    OR (version_compare(PHP_VERSION, '8.1.0', '>=') AND version_compare(PHP_VERSION, '8.1.5', '<='))) {
				$line_key++;
			}
			$line = trim($line);
			if (!$line) continue;
			if (!empty($_GET['q'])) {
				if (!zz_maintenance_searched($line)) continue;
			}
			if (!empty($_GET['filter']['type']) OR !empty($_GET['filter']['level']) OR $data['group']) {
				if (substr($line, 0, 1) === '[') $line = substr($line, strpos($line, ']') + 2);
			}
			if (!empty($_GET['filter']['type']) OR $data['group']) {
				$type = substr($line, 0, strpos($line, ' '));
				if (!in_array($type, $filters['type'])) $type = '';
				if (!empty($_GET['filter']['type']) AND $type !== $_GET['filter']['type']) continue;
			}
			if (!empty($_GET['filter']['level']) OR $data['group']) {
				$start = strpos($line, ' ') + 1;
				$level = substr($line, $start, strpos($line, ':') - $start);
				if (!in_array($level, $filters['level'])) $level = '';
				if (!empty($_GET['filter']['level']) AND $level !== $_GET['filter']['level']) continue;
			}
			if ($data['group'] AND empty($_POST['deleteall'])) {
				// not necessary for deleteall to group entries
				if ($type) $line = substr($line, strlen($type) + 1);
				if ($level) $line = substr($line, strlen($level) + 2);
				$line = trim($line);
				// user?
				if (in_array($type, ['zzform', 'zzwrap'])) {
					if (substr($line, -1) === ']')
						$line = trim(substr($line, 0, strrpos($line, '[')));
					if (substr($line, 0, 1) === '[')
						$line = trim(substr($line, strpos($line, ']') + 1));
				}
				$found[$line][] = $line_key;
			} else {
				$found[] = $line_key;
			}
		}
		$data['total_rows'] = count($found);
	} else {
		$file->seek(PHP_INT_MAX);
		$data['total_rows'] = $file->key();
	}
	if (!empty($_GET['limit']) AND $_GET['limit'] === 'last') {
		zz_list_limit_last($data['total_rows']); // not + 1 since logs always end with a newline
	}

	if (!empty($_POST['deleteall'])) {
		$data['message'] .= wrap_file_delete_line($logfile, $found);
		// show other records without search filter
		unset($found);
		$file->seek(PHP_INT_MAX);
		$data['total_rows'] = $file->key();
		// remove 'q' from query string
		$zz_conf['int']['url']['qs_zzform'] = zz_edit_query_string($zz_conf['int']['url']['qs_zzform'], ['q', 'scope']);
		$request_uri = parse_url($_SERVER['REQUEST_URI']);
		$request_uri['query'] = zz_edit_query_string($request_uri['query'], ['q', 'scope']);
		$_SERVER['REQUEST_URI'] = http_build_query($request_uri);
	}

	if ($zz_conf['int']['this_limit']) {
		if (isset($found)) {
			$found = array_slice($found, ($zz_conf['int']['this_limit'] - wrap_setting('zzform_limit')), wrap_setting('zzform_limit'));
			if ($data['group'] AND empty($_POST['deleteall'])) {
				$group = $found;
				$found = [];
				foreach ($group as $lines) {
					$found = array_merge($found, $lines);
				}
				$group = array_values($group);
				sort($found);
			}
		} else {
			$found = range(
				$zz_conf['int']['this_limit'] - wrap_setting('zzform_limit'),
				($data['total_rows'] < $zz_conf['int']['this_limit'] ? $data['total_rows'] : $zz_conf['int']['this_limit']) - 1
			);
		}
	} else {
		$found = range(0, $data['total_rows']);	
	}

	// output lines
	$data['lines'] = [];
	if ($data['total_rows']) {
		foreach ($found as $index) {
			$file->seek($index);
			$data['lines'][$index] = zz_maintenance_logs_line($file->current(), $filters['type']);
			$data['lines'][$index]['no'] = $index;
			$data['lines'][$index]['keys'] = $index;
		}
	}

	if ($data['group']) {
		$data['lines'] = mod_default_make_log_group($data['lines'], $group);
	}

	$data['url_self'] = wrap_html_escape($_SERVER['REQUEST_URI']);
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($zz_conf['int']['this_limit'], $data['total_rows']);
	wrap_setting('zzform_search_form_always', true);
	$searchform = zz_search_form([], '', $data['total_rows'], $data['total_rows']);
	$data['searchform'] = $searchform['bottom'];

	$page['text'] = wrap_template('maintenance-logs', $data);
	$page['text'] .= wrap_template('zzform-foot');
	return $page;
}

/**
 * output filters for log files
 *
 * @param array $filter
 * @global array $zz_conf
 * @return string
 */
function mod_default_make_log_filter($filters) {
	global $zz_conf;
	$f_output = [];

	parse_str($zz_conf['int']['url']['qs_zzform'], $my_query);
	$filters_set = (!empty($my_query['filter']) ? $my_query['filter'] : []);
	$unwanted_keys = ['filter', 'limit'];
	$my_uri = $zz_conf['int']['url']['self'].zz_edit_query_string($zz_conf['int']['url']['qs_zzform'], $unwanted_keys);

	if (count($filters['type']) === 1) unset($filters['type']);
	foreach ($filters as $index => $filter) {
		$f_output[$index]['title'] = wrap_text(ucfirst($index));
		$my_link = $my_uri;
		if ($filters_set) {
			foreach ($filters_set as $which => $filter_set) {
				if ($which != $index) $my_link .= '&amp;filter['.$which.']='.urlencode($filter_set);
			}
		}
		foreach ($filter as $value) {
			$is_selected = ((isset($_GET['filter'][$index]) 
				AND $_GET['filter'][$index] == $value)) ? true : false;
			$link = $my_link.'&amp;filter['.$index.']='.urlencode($value);
			$f_output[$index]['values'][] = [
				'link' => !$is_selected ? $link : '',
				'title' => wrap_text($value)
			];
		}
		$f_output[$index]['values'][] = [
			'all' => true,
			'link' => isset($_GET['filter'][$index]) ? $my_link : ''
		];
	}
	if (!$f_output) return '';
	$f_output = array_values($f_output);
	return wrap_template('zzform-list-filter', $f_output);
}

/**
 * group lines in log (identical lines are combined)
 *
 * @param array $raw
 * @param array $group
 * @return array
 */
function mod_default_make_log_group($raw, $group) {
	$out = [];
	foreach ($group as $gindex => $lines) {
		if (count($lines) === 1) {
			$raw[$lines[0]]['count'] = 1;
			$out[] = $raw[$lines[0]];
			continue;
		}
		$my = [
			'user' => [],
			'links' => [],
			'status' => [],
			'time' => [],
			'index' => []
		];
		$date_end = '';
		foreach ($lines as $lindex => $line) {
			$my['index'][] = $raw[$line]['no'];
			if ($raw[$line]['time']) $my['time'][] = $raw[$line]['time'];
			if ($lindex AND $lindex === count($lines) - 1
				AND $raw[$lines[0]]['date'] !== $raw[$line]['date']) {
				$date_end = $raw[$line]['date'];
			}
			if ($raw[$line]['user'] AND !in_array($raw[$line]['user'], $my['user']))
				$my['user'][] = $raw[$line]['user'];
			if ($raw[$line]['links'] AND !in_array($raw[$line]['links'], $my['links']))
				$my['links'][] = $raw[$line]['links'];
			if ($raw[$line]['status'] AND !in_array($raw[$line]['status'], $my['status']))
				$my['status'][] = $raw[$line]['status'];
		}
		$out[] = [
			'count' => count($lines),
			'no' => $gindex,
			'date_begin' => $raw[$lines[0]]['date'],
			'date_end' => $date_end,
			'type' => $raw[$lines[0]]['type'],
			'level' => $raw[$lines[0]]['level'],
			'error' => $raw[$lines[0]]['error'],
			'status' => implode(' ', $my['status']),
			'user' => implode(', ', $my['user']),
			'keys' => implode(',', $my['index']),
			'time' => implode(', ', $my['time']),
			'links' => implode('', $my['links'])
		];
	}
	return $out;
}

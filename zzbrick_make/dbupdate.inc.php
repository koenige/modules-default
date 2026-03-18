<?php 

/**
 * default module
 * update database structure/content
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * update database structure and content that is related to the structure
 * based on update.sql per module
 *
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_dbupdate($params) {
	// look for update.sql
	$data = [];
	$files = wrap_collect_files('configuration/update.sql', 'modules/custom');
	wrap_sql_ignores();
	foreach ($files as $module => $file) {
		$data = array_merge($data, mod_default_make_dbupdate_readfile($file, $module));
	}
	ksort($data);
	mod_default_make_dbupdate_log(array_keys($data), 'structure_check');
	$data = array_values($data);

	foreach ($data as $index => $line) {
		$data[$index]['index'] = $index;
		$data[$index]['exists'] = mod_default_make_dbupdate_check($line);
		if (!$data[$index]['exists']) {
			$data[$index]['current'] = true;
			$current = $index;
			break;
		} elseif ($data[$index]['exists'] === -1) {
			unset($data[$index]);
		}
	}

	// after unsetting entries, normalize indices and current position
	$data = array_values($data);
	foreach ($data as $i => $line) $data[$i]['index'] = $i;
	unset($current);
	foreach ($data as $i => $line) {
		if (!empty($line['current'])) {
			$current = $i;
			break;
		}
	}

	// POST update/ignore: same handler for form submit and XHR
	if ($_SERVER['REQUEST_METHOD'] === 'POST'
		AND isset($current) AND array_key_exists($current, $data)
		AND isset($_POST['index']) AND strval($current) === strval($_POST['index'])) {
		$is_xhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		if (array_key_exists('update', $_POST)) {
			$ok = mod_default_make_dbupdate_update($data[$current], $is_xhr);
			if ($is_xhr) return mod_default_make_dbupdate_json_response($data, $current, $ok);
		} elseif (array_key_exists('ignore', $_POST)) {
			mod_default_make_dbupdate_ignore($data[$current], $is_xhr);
			if ($is_xhr) return mod_default_make_dbupdate_json_response($data, $current, true);
		}
	}

	$history = !empty($params[0]) && $params[0] === 'history';
	if ($history) {
		$page['title'] = wrap_text('Database Updates History');
		$page['breadcrumbs'][] = ['title' => wrap_text('Database Updates'), 'url_path' => '?dbupdate'];
		$page['breadcrumbs'][]['title'] = wrap_text('History');
	} else {
		$past_limit = wrap_setting('default_dbupdate_past_limit');
		if (isset($current))
			$data = array_slice($data, max(0, $current - $past_limit));
		else
			$data = array_slice($data, -$past_limit);
		$data = array_values($data);
		$page['title'] = wrap_text('Database Updates');
		$page['breadcrumbs'][]['title'] = wrap_text('Database Updates');
	}

	$data['history'] = $history;
	$data['no_pending'] = !$history && !isset($current);
	$page['text'] = wrap_template('dbupdate', $data);
	$page['text'] = str_replace('%%%', '%%&#8239;%', $page['text']);
	return $page;
}

/**
 * return JSON for XHR after update/ignore
 *
 * @param array $data
 * @param int $current
 * @param bool $ok
 * @return array $page
 */
function mod_default_make_dbupdate_json_response($data, $current, $ok) {
	$page = [];
	$page['content_type'] = 'json';
	if (isset($_GET['dbupdate'])) $page['query_strings'][] = 'dbupdate';
	if (!$ok) {
		$page['status'] = 500;
		$page['text'] = json_encode(['ok' => false, 'error' => wrap_text('Could not update database.')]);
		return $page;
	}
	$next_index = null;
	for ($i = $current + 1; $i < count($data); $i++) {
		if (mod_default_make_dbupdate_check($data[$i]) === false) {
			$next_index = $i;
			break;
		}
	}
	$done = $next_index === null;
	$out = ['ok' => true, 'done' => $done, 'next_index' => $next_index];
	if ($done) {
		$count = isset($_POST['count']) && is_numeric($_POST['count']) ? (int) $_POST['count'] : 1;
		$out['message'] = wrap_text('%d queries were executed.', ['values' => [$count]])
			. ' ' . wrap_text('No pending SQL updates.');
	}
	$page['text'] = json_encode($out);
	return $page;
}

/**
 * read a .sql file and interpret it
 *
 * @param string $filename
 * @return array
 */
function mod_default_make_dbupdate_readfile($filename, $module) {
	$data = [];
	$lines = wrap_sql_file($filename);
	if (!$lines) return [];
	foreach ($lines['file'] as $line) {
		$line = explode("\t", $line);
		$line[0] = ltrim($line[0], '/* ');
		$line[0] = rtrim($line[0], ' */');
		$line[0] = explode('-', $line[0]);
		$key = vsprintf('%04d-%02d-%02d-%06d-%\'_12s', array_merge($line[0], [$module]));
		$query = rtrim(trim($line[1]), ';');
		$table = mod_default_make_dbupdate_table($query);
		if (!$table OR !wrap_sql_ignores($module, $table)) {
			$data[$key] = [
				'query' => $query,
				'module' => $module,
				'date' => vsprintf('%04d-%02d-%02d', $line[0]),
				'key' => $key
			];
		}
	}
	return $data;
}

/**
 * get table name from query
 *
 * @param string $query
 * @return string
 */
function mod_default_make_dbupdate_table($query) {
	$sql_verbs = [
		'ALTER TABLE', 'DELETE FROM', 'UPDATE', 'INSERT INTO', 'CREATE TABLE',
		'DROP TABLE', 'DELETE'
	];
	foreach ($sql_verbs as $verb) {
		if (str_starts_with($query, $verb.' ')) {
			$table = substr($query, strlen($verb) + 1);
			break;
		}
	}
	if (!empty($table)) {
		if ($pos = strpos($table, ' ')) $table = substr($table, 0, $pos);
		$table = trim($table, '`');
		return $table;
	}
	return '';
}

/**
 * check if update already happened
 *
 * @param array $line
 * @return int (bool true, false; -1 if before install date)
 */
function mod_default_make_dbupdate_check($line) {
	// install date and is it before log date?
	if ($install_date = wrap_setting('zzwrap_install_date')) {
		if ($line['date'] < substr($install_date, 0, 10)) return -1;
	}
	$module_install_key = sprintf('mod_%s_install_date', $line['module']);
	if (wrap_setting($module_install_key)) {
		if ($line['date'] < substr(wrap_setting($module_install_key), 0, 10)) return -1;
	}
	
	// update already in log?
	$success = mod_default_make_dbupdate_log($line, 'read');
	if ($success) {
		return true;
	}

	// update already in logging table?
	$sql = 'SELECT log_id FROM /*_TABLE zzform_logging _*/
		WHERE query = "%s" AND last_update > "%s"';
	$sql = sprintf($sql, wrap_db_escape($line['query']), $line['date']);
	$record = wrap_db_fetch($sql);
	if ($record) {
		mod_default_make_dbupdate_log($line, 'exists');
		return true;
	}

	return false;
}

/**
 * do update (same logic for form submit and XHR; $ajax true = return bool, false = redirect or error)
 *
 * @param array $line
 * @param bool $ajax
 * @return bool
 */
function mod_default_make_dbupdate_update($line, $ajax = false) {
	wrap_include('database', 'zzform');

	$result = wrap_db_query($line['query']);
	if ($result) {
		$log = true;
		if (is_array($result) AND array_key_exists('rows', $result) AND !$result['rows']) {
			// no changes were made, do not log
			$log = false;
		}
		if ($log) zz_db_log($line['query'], 'Maintenance robot 476');
		mod_default_make_dbupdate_log($line, 'update');
		if ($ajax) return true;
		wrap_redirect_change('#current');
	}
	if ($ajax) return false;
	wrap_error('Could not update database', E_USER_ERROR);
}

/**
 * ignore update
 *
 * @param array $line
 * @param bool $ajax
 * @return void
 */
function mod_default_make_dbupdate_ignore($line, $ajax = false) {
	mod_default_make_dbupdate_log($line, 'ignore');
	if (!$ajax) wrap_redirect_change('#current');
}

/**
 * read or write database update log
 *
 * @param array $line
 * @param string $mode
 * @return bool
 */
function mod_default_make_dbupdate_log($line, $mode) {
	$logfile = wrap_setting('log_dir').'/dbupdate.log';
	if (!file_exists($logfile)) touch($logfile);
	switch ($mode) {
	case 'structure_check':
		// check structure
		$logs = file($logfile);
		if (!$logs) return true;
		$log = explode(' ', $logs[0]);
		if (strlen($log[0]) === 30) return true;
		rename($logfile, wrap_setting('log_dir').'/dbupdate-bak.log');
		touch($logfile);

		foreach ($logs as $index => $log) {
			$log = explode(' ', $log);
			foreach ($line as $index => $key) {
				if (!str_starts_with($key, $log[0])) continue;
				$log[0] = $key;
				error_log(implode(' ', $log), 3, $logfile);
			}
		}
		unlink(wrap_setting('log_dir').'/dbupdate-bak.log');
		return true;
	case 'read':
		$logs = file($logfile);
		foreach ($logs as $log) {
			$log = explode(' ', $log);
			if ($log[0] !== $line['key']) continue;
			return true;
		}
		break;
	case 'ignore':
	case 'update':
	case 'exists':
		error_log(sprintf("%s %s %s %s\n", $line['key'], date('Y-m-d H:i:s'), $mode, $_SESSION['username']), 3, $logfile);		
		return true;
	}
	return false;
}

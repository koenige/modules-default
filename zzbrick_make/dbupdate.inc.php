<?php 

/**
 * default module
 * update database structure/content
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2024 Gustaf Mossakowski
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
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST'
		AND isset($current) AND array_key_exists($current, $data)
		AND isset($_POST['index']) AND strval($current) === strval($_POST['index'])) {
		if (array_key_exists('update', $_POST))
			mod_default_make_dbupdate_update($data[$current]);
		elseif (array_key_exists('ignore', $_POST))
			mod_default_make_dbupdate_ignore($data[$current]);
	}

	$page['text'] = wrap_template('dbupdate', $data);
	$page['text'] = str_replace('%%%', '%%&#8239;%', $page['text']);
	$page['title'] = wrap_text('Database Updates');
	$page['breadcrumbs'][]['title'] = wrap_text('Database Updates');
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
 * do update
 *
 * @param array $line
 * @return void
 */
function mod_default_make_dbupdate_update($line) {
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
		wrap_redirect_change();
	}
	wrap_error('Could not update database', E_USER_ERROR);
}

/**
 * ignore update
 *
 * @param array $line
 * @return void
 */
function mod_default_make_dbupdate_ignore($line) {
	mod_default_make_dbupdate_log($line, 'ignore');
	wrap_redirect_change();
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

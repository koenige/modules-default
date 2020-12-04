<?php 

/**
 * default module
 * update database structure/content
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * update database structure and content that is related to the structure
 * based on update.sql per module
 *
 * @param array $params
 * @global array $zz_setting
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_dbupdate($params) {
	global $zz_setting;

	// look for update.sql
	$data = [];
	$file_template = $zz_setting['modules_dir'].'/%s/docs/sql/update.sql';
	foreach ($zz_setting['modules'] as $module) {
		$file = sprintf($file_template, $module);
		if (!file_exists($file)) continue;
		$data = array_merge($data, mod_default_make_dbupdate_readfile($file, $module));
	}
	ksort($data);
	$data = array_values($data);

	foreach ($data as $index => $line) {
		$data[$index]['index'] = $index;
		$data[$index]['exists'] = mod_default_make_dbupdate_check($line);
		if (!$data[$index]['exists']) {
			$data[$index]['current'] = true;
			$current = $index;
			break;
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
	$page['breadcrumbs'][] = wrap_text('Database Updates');
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
	$lines = file($filename);
	foreach ($lines as $line) {
		$line = trim($line);
		if (!$line) continue;
		if (substr($line, 0, 3) === '/**') continue;
		if (substr($line, 0, 1) === '*') continue;
		if (substr($line, 0, 2) === '*/') continue;
		$line = explode("\t", $line);
		$line[0] = ltrim($line[0], '/* ');
		$line[0] = rtrim($line[0], ' */');
		$line[0] = explode('-', $line[0]);
		$key = vsprintf('%04d-%02d-%02d-%06d', $line[0]);
		$data[$key] = [
			'query' => rtrim(trim($line[1]), ';'),
			'module' => $module,
			'date' => vsprintf('%04d-%02d-%02d', $line[0]),
			'key' => $key
		];
	}
	return $data;
}

/**
 * check if update already happened
 *
 * @param array $line
 * @return bool
 */
function mod_default_make_dbupdate_check($line) {
	global $zz_conf;
	
	// install date and is it before log date?
	if ($install_date = wrap_get_setting('zzwrap_install_date')) {
		if ($line['date'] < substr($install_date, 0, 10)) return true;
	}
	
	// update already in log?
	$success = mod_default_make_dbupdate_log($line, 'read');
	if ($success) {
		return true;
	}

	// update already in logging table?
	$sql = 'SELECT log_id FROM %s WHERE query = "%s" AND last_update > "%s"';
	$sql = sprintf($sql, $zz_conf['logging_table'], wrap_db_escape($line['query']), $line['date']);
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
	global $zz_conf;
	require_once $zz_conf['dir_inc'].'/database.inc.php';

	$result = wrap_db_query($line['query']);
	if ($result) {
		$log = true;
		if (is_array($result) AND array_key_exists('rows', $result) AND !$result['rows']) {
			// no changes were made, do not log
			$log = false;
		}
		if ($log) zz_log_sql($line['query'], 'Maintenance robot 476');
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
	global $zz_setting;

	$logfile = $zz_setting['log_dir'].'/dbupdate.log';
	if (!file_exists($logfile)) touch($logfile);
	switch ($mode) {
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

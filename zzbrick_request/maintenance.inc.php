<?php 

/**
 * default module
 * Maintenance script for database operations with zzform
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2017 Gustaf Mossakowski
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
 * @global array $zz_setting
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_maintenance($params) {
	global $zz_conf;
	global $zz_setting;

	if (!isset($zz_conf['modules'])) {
		$zz_conf['modules'] = [];
		$zz_conf['modules']['debug'] = false;
	}
	$zz_setting['extra_http_headers'][] = 'X-Frame-Options: Deny';
	$zz_setting['extra_http_headers'][] = "Content-Security-Policy: frame-ancestors 'self'";

	if (file_exists($file = $zz_setting['custom'].'/zzbrick_tables/_common.inc.php')) {
		// e. g. heading_prefix
		require_once $file;
	}
	if (isset($brick['page'])) $page = $brick['page'];
	$page['head'] = isset($page['head']) ? $page['head'] : '';
	$page['head'] .= wrap_template('zzform-head', $zz_setting);
	$page['title'] = !empty($zz_conf['heading_prefix']) ? wrap_text($zz_conf['heading_prefix']) : '';
	if (!empty($_GET) OR !empty($_POST)) {
		$page['title'] .= ' <a href="./">'.wrap_text('Maintenance').'</a>:';
		$page['breadcrumbs'][] = '<a href="./">'.wrap_text('Maintenance').'</a>';
	}

	if (!empty($_POST['sql'])) {
		return zz_maintenance_sqlquery($page);
	} elseif (!empty($_POST['sqlupload'])) {
		return zz_maintenance_sqlupload($page);
	} elseif (isset($_GET['sqldownload'])) {
		return zz_maintenance_sqldownload($page);
	} elseif (isset($_GET['filetree'])) {
		return zz_maintenance_filetree($page);
	} elseif (!empty($_GET['folder'])) {
		return zz_maintenance_folders($page);
	} elseif (!empty($_GET['log'])) {
		return zz_maintenance_logs($page);
	} elseif (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif (isset($_GET['integrity'])) {
		return zz_maintenance_integrity($page);
	}

	$data = [];
	$data = array_merge($data, zz_maintenance_tables());
	$data['errors'] = zz_maintenance_errors();
	// zz_write_conf()
	require_once $zz_conf['dir'].'/zzform.php';
	require_once $zz_conf['dir'].'/functions.inc.php';
	require_once $zz_conf['dir'].'/upload.inc.php';
	zz_upload_config();
	if ($zz_conf['graphics_library'] === 'imagemagick') {
		require_once $zz_conf['dir'].'/image-imagemagick.inc.php';
		$data['imagick'] = zz_imagick_version();
		$data['ghostscript'] = zz_ghostscript_version();
	}
	$folders = zz_maintenance_folders();
	$data['folders'] = $folders['text'];
	$data['logging_table'] = !empty($zz_conf['logging_table']) ? $zz_conf['logging_table'] : '';

	$page['text'] = wrap_template('maintenance', $data);
	$page['title'] .= ' '.wrap_text('Maintenance');
	$page['breadcrumbs'][] = wrap_text('Maintenance');
	return $page;
}

/**
 * change the database with a custom SQL query which is logged
 *
 * @param array $page
 * @return array
 */
function zz_maintenance_sqlquery($page) {
	global $zz_conf;
	// zz_htmltag_escape()
	require_once $zz_conf['dir_inc'].'/functions.inc.php';
	// zz_db_change()
	require_once $zz_conf['dir_inc'].'/database.inc.php';

	if (!empty($_SESSION) AND empty($zz_conf['user']) AND !empty($zz_setting['brick_username_in_session']))
		$zz_conf['user'] = $_SESSION[$zz_setting['brick_username_in_session']];
	elseif (!isset($zz_conf['user']))
		$zz_conf['user'] = 'Maintenance robot 812';

	$result = [];
	$sql = $_POST['sql'];
	$statement = zz_db_statement($sql);

	switch ($statement) {
	case 'INSERT':
	case 'UPDATE':
	case 'DELETE':
	case 'CREATE TABLE':
	case 'ALTER TABLE':
	case 'CREATE VIEW':
	case 'ALTER VIEW':
		$result = zz_db_change($sql);
		$result['change'] = true;
		if (!$result['action']) {
			if (empty($result['error']['db_msg']) AND !empty($result['error']['msg_dev'])) {
				$result['error_db_msg'] = vsprintf(wrap_text($result['error']['msg_dev']), $result['error']['msg_dev_args']);
			} else {
				$result['error_db_msg'] = $result['error']['db_msg'];
				$result['error_db_errno'] = $result['error']['db_errno'];
			}
		} elseif ($result['action'] === 'nothing') {
			$result['action_nothing'] = true;
		} else {
			$result['action'] = wrap_text(ucfirst($result['action']));
		}
		break;
	case 'SELECT':
	default:
		$result['not_supported'] = true;
		$result['token'] = zz_htmltag_escape($statement);
		break;
	}
		
	$result['sql'] = zz_maintenance_sql($sql);
	$result['form_sql'] = str_replace('%%%', '%&shy;%&shy;%', wrap_html_escape($sql));

	$page['title'] .= ' '.wrap_text('SQL Query');
	$page['breadcrumbs'][] = wrap_text('SQL Query');
	$page['text'] = wrap_template('maintenance-sql', $result);
	return $page;
}

/**
 * list and modify databases for translation and relation tables
 *
 * @return array
 */
function zz_maintenance_tables() {
	global $zz_conf;
	$data = [];

	$data['relations_table'] = $zz_conf['relations_table'];
	$data['translations_table'] = !empty($zz_conf['translations_table']) ? $zz_conf['translations_table'] : false;
	if (empty($zz_conf['relations_table']) AND empty($zz_conf['translations_table']))
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
						$table = $zz_conf['translations_table'];
						$field_name = 'db_name';
					} else {
						$table = $zz_conf['relations_table'];
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
	}
	if (!empty($zz_conf['relations_table'])) {
	// Master database
		$sql = 'SELECT DISTINCT master_db FROM %s';
		$sql = sprintf($sql, $zz_conf['relations_table']);
		$dbs['master'] = wrap_db_fetch($sql, 'master_db', 'single value');

	// Detail database	
		$sql = 'SELECT DISTINCT detail_db FROM %s';
		$sql = sprintf($sql, $zz_conf['relations_table']);
		$dbs['detail'] = wrap_db_fetch($sql, 'detail_db', 'single value');
	}

	if (!empty($zz_conf['translations_table'])) {
	// Translations database	
		$sql = 'SELECT DISTINCT db_name FROM %s';
		$sql = sprintf($sql, $zz_conf['translations_table']);
		$dbs['translation'] = wrap_db_fetch($sql, 'db_name', 'single value');
	}
	
	// All available databases
	$sql = 'SHOW DATABASES';
	$databases = wrap_db_fetch($sql, 'Databases', 'single value');
	foreach ($databases as $db) $db_list[] = ['db' => $db];

	$i = 0;
	foreach ($dbs as $category => $db_names) {
		foreach ($db_names as $db) {
			$data['tables'][] = [
				'title' => wrap_text(ucfirst($category)),
				'db' => $db,
				'category' => $category,
				'keep' => in_array($db, $databases) ? true : false,
				'databases' => $db_list
			];
		}
	}
	return $data;
}

/**
 * checks all fields that have an entry in the relations_table if they
 * contain invalid values (e. g. values that do not have a corresponding value
 * in the master table
 *
 * @param array $page
 * @global array $zz_conf 'relations_table'
 * @return string text output
 * @todo add translations with wrap_text()
 */
function zz_maintenance_integrity($page) {
	global $zz_conf;

	$page['title'] .= ' '.wrap_text('Relational Integrity');
	$page['breadcrumbs'][] = wrap_text('Relational Integrity');
	$page['query_strings'][] = 'integrity';

	$sql = 'SELECT * FROM %s';
	$sql = sprintf($sql, $zz_conf['relations_table']);
	$relations = wrap_db_fetch($sql, 'rel_id');

	$results = [];
	foreach ($relations as $relation) {
		$sql = 'SELECT DISTINCT detail_table.`%s`
				, detail_table.`%s`
			FROM `%s`.`%s` detail_table
			LEFT JOIN `%s`.`%s` master_table
				ON detail_table.`%s` = master_table.`%s`
			WHERE ISNULL(master_table.`%s`)
			AND !ISNULL(detail_table.`%s`)
		';
		$sql = sprintf($sql,
			$relation['detail_id_field'], $relation['detail_field'],
			$relation['detail_db'], $relation['detail_table'],
			$relation['master_db'], $relation['master_table'],
			$relation['detail_field'], $relation['master_field'],
			$relation['master_field'], $relation['detail_field']
		);
		$ids = wrap_db_fetch($sql, '_dummy_', 'key/value', false, E_USER_NOTICE);
		$detail_field = $relation['detail_db'].' . '.$relation['detail_table'].' . '.$relation['detail_field'];
		if ($ids) {
			$results[] = '<li class="error">'.wrap_text('Error').' &#8211; '
				.sprintf(wrap_text('Field %s contains invalid values:'),
				'<code>'.$detail_field.'</code>').' ('
				.$relation['detail_id_field'].' => '.$relation['detail_field'].')<br>';
			$line = '';
			foreach ($ids as $id => $foreign_id) {
				$line .= $id.' => '.$foreign_id.'; ';
			}
			$line .= '</li>';
			$results[] = $line;
		} else {
			$results[] = '<li class="ok">'.wrap_text('OK').' &#8211; '
				.sprintf(wrap_text('Field %s contains only valid values.'),
				'<code>'.$detail_field.'</code>').'</li>';
		}
	}
	if ($results) {
		$page['text'] = "<ul>".implode("\n", $results)."</ul>\n";
	} else {
		$page['text'] = wrap_text('Nothing to check.');
	}
	return mod_default_maintenance_return($page);
}

/**
 * show filetree
 *
 * @param array $page
 * @return array
 */
function zz_maintenance_filetree($page) {
	global $zz_conf;

	$page['title'] .= ' '.wrap_text('Filetree');
	$page['breadcrumbs'][] = wrap_text('Filetree');
	$page['query_strings'][] = 'filetree';

	// zz_htmltag_escape()
	require_once $zz_conf['dir_inc'].'/functions.inc.php';

	$files = [];
	$topdir = $_SERVER['DOCUMENT_ROOT'].'/../';
	$base = false;
	if (!empty($_GET['filetree'])) {
		$files['parts'] = [];
		$parts = explode('/', $_GET['filetree']);
		$text = array_pop($parts);
		$files['parts'][] = ['title' => zz_htmltag_escape($text)];
		while ($parts) {
			$folder = implode('/', $parts);
			$part = array_pop($parts);
			$files['parts'][] = [
				'title' => zz_htmltag_escape($part),
				'link' => $folder
			];
		}
		$files['parts'] = array_reverse($files['parts']);
		$base = $_GET['filetree'].'/';
	}
	$files += zz_maintenance_files($topdir.$base, $base);
	$page['text'] = wrap_template('maintenance-filetree', $files);
	return $page;
}

/**
 * show files in a directory, directory links to sub directories
 *
 * @param string $dir
 * @param string $base
 * @return string
 */
function zz_maintenance_files($dir, $base) {
	if (!is_dir($dir)) return [];

	$i = 0;
	$data = [];
	$data['total'] = 0;
	$data['totalfiles'] = 0;
	$files = [];

	$handle = opendir($dir);
	while ($file = readdir($handle)) {
		if ($file === '.' OR $file === '..') continue;
		$files[] = $file;
	}
	closedir($handle);
	sort($files);

	foreach ($files as $file) {
		$i++;
		$files_in_folder = 0;
		$path = $dir.'/'.$file;
		if (is_dir($path)) {
			list ($size, $files_in_folder) = zz_maintenance_dirsize($path);
			$link = $base.$file;
		} else {
			$size = filesize($path);
			$files_in_folder = 1;
			$link = '';
		}
		$data['files'][] = [
			'file' => $file,
			'link' => $link,
			'size' => $size,
			'files_in_folder' => $files_in_folder
		];
		$data['total'] += $size;
		$data['totalfiles'] += $files_in_folder;
	}
	return $data;
}

/**
 * get size of directory and files inside, recursively
 *
 * @param string $dir absolute path of directory
 * @return array
 */
function zz_maintenance_dirsize($dir) {
	$size = 0;
	$files = 0;
	$handle = opendir($dir);
	if (!$handle) return [$size, $files];
	while ($file = readdir($handle)) {
		if ($file === '.' OR $file === '..') continue;
		if (is_dir($dir.'/'.$file)) {
			list ($mysize, $myfiles) = zz_maintenance_dirsize($dir.'/'.$file);
			$size += $mysize;
			$files += $myfiles;
		} else {
			$size += filesize($dir.'/'.$file);
			$files++;
		}
	}
	closedir($handle);
	return [$size, $files];
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

function zz_maintenance_folders($page = []) {
	global $zz_conf;
	global $zz_setting;
	
	$data = [];
	if ($page) {
		$page['title'] .= ' '.wrap_text('Backup folder');
		$page['breadcrumbs'][] = wrap_text('Backup folder');
		$page['query_strings'] = [
			'folder', 'file', 'q', 'scope', 'deleteall', 'limit'
		];
		$data['folder'] = true;
		zz_maintenance_list_init();
	}

	if (!isset($zz_conf['backup'])) $zz_conf['backup'] = '';
	if ((!$zz_conf['backup'] OR empty($zz_conf['backup_dir']))
		AND empty($zz_conf['tmp_dir']) AND empty($zz_setting['cache_dir'])) {
		$page['text'] = '<p>'.wrap_text('Backup of uploaded files is not active.').'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	$folders = [];
	$dirs = [
		'TEMP' => $zz_conf['tmp_dir'],
		'BACKUP' => $zz_conf['backup_dir'],
		'CACHE' => $zz_setting['cache_dir']
	];
	foreach ($dirs as $key => $dir) {
		$exists = file_exists($dir) ? true : false;
		$data['paths'][] = [
			'key' => $key,
			'dir' => realpath($dir),
			'not_exists' => !$exists AND $dir ? true: false
		];
		if (!$exists) continue;
		$folders[] = $key;
		if (substr($dir, -1) === '/') $dir = substr($dir, 0, -1);
		if (!empty($_GET['folder']) AND substr($_GET['folder'], 0, strlen($key)) === $key) {
			$my_folder = $dir.substr($_GET['folder'], strlen($key));
		}
	}

	if (!empty($_GET['folder']) AND !empty($_GET['file'])) {
		$file['name'] = $my_folder.'/'.$_GET['file'];
		wrap_file_send($file);
		exit;
	}

	// delete
	$deleted = 0;
	if (!empty($_POST['files']) AND !empty($_GET['folder'])) {
		foreach ($_POST['files'] as $file => $bool) {
			if ($bool != 'on') continue;
			if (file_exists($my_folder.'/'.$file)) {
				if (is_dir($my_folder.'/'.$file)) {
					rmdir($my_folder.'/'.$file);
					$deleted++;
				} else {
					unlink($my_folder.'/'.$file);
					$deleted++;
				}
			}
		}
	}

	foreach ($folders as $index => $folder) {
		$data['folders'][$index]['title'] = $folder;
		$data['folders'][$index]['hide_content'] = true;
		if (empty($_GET['folder'])) continue;
		if (substr($_GET['folder'], 0, strlen($folder)) != $folder) continue;
		$data['folders'][$index]['hide_content'] = false;
		if ($folder !== $_GET['folder']) {
			$data['folders'][$index]['subtitle'] = zz_htmltag_escape($_GET['folder']);
		}

		$folder_handle = opendir($my_folder);

		$files = [];
		$total_files_q = 0;
		while ($file = readdir($folder_handle)) {
			if (substr($file, 0, 1) === '.') continue;
			if (!empty($_POST['deleteall'])) {
				$deleted += zz_maintenance_folders_deleteall($my_folder, $file);
				continue;
			}
			$files[] = $file;
			if (zz_maintenance_searched($file)) {
				$total_files_q++;
			}
		}
		sort($files);
		if ($deleted) {
			$data['folders'][$index]['deleted'] = $deleted;
		}

		list($data['folders'][$index]['deleteall_url'], $data['folders'][$index]['deleteall_filter']) = zz_maintenance_deleteall_form();
		if ($data['folders'][$index]['deleteall_url']) {
			$page['text'] = wrap_template('maintenance-folders', $data);
			return $page;
		}

		$i = 0;
		$data['folders'][$index]['size_total'] = 0;
		$data['folders'][$index]['files_total'] = 0;
		foreach ($files as $filename) {
			if (!empty($_GET['q']) AND !zz_maintenance_searched($filename)) {
				continue;
			}
			if ($i < $zz_conf['int']['this_limit'] - $zz_conf['limit']) {
				$i++;
				continue;
			}
			$path = $my_folder.'/'.$filename;
			$file = [];
			$file['file'] = $filename;
			$file['size'] = filesize($path);
			$data['folders'][$index]['size_total'] += $file['size'];
			if (is_dir($path)) {
				$file['ext'] = wrap_text('Folder');
			} elseif (strrpos($filename, '.') > strlen($filename) - 10) {
				// treat part behind last dot as file extension
				// normally, file extensions won't be longer than 10 characters
				// not 100% correct of course
				$file['ext'] = substr($filename, strrpos($filename, '.') + 1);
			} else {
				$file['ext'] = wrap_text('unknown');
			}
			$file['time'] = date('Y-m-d H:i:s', filemtime($path));
			$file['files_in_dir'] = 0;
			if (is_dir($path)) {
				$file['dir'] = true;
				$file['link'] = urlencode($_GET['folder']).'/'.urlencode($filename);
				$subfolder_handle = opendir($path);
				while ($subdir = readdir($subfolder_handle)) {
					if (substr($subdir, 0, 1) === '.') continue;
					$file['files_in_dir']++;
				}
				closedir($subfolder_handle);
			} else {
				$file['link'] = urlencode($_GET['folder']).'&amp;file='.urlencode($filename);
			}
			if (!$file['files_in_dir']) $file['files_in_dir'] = NULL;
			$file['title'] = zz_mark_search_string(str_replace('%', '%&shy;', wrap_html_escape(urldecode($filename))));
			$data['folders'][$index]['files'][] = $file;
			$i++;
			$data['folders'][$index]['files_total']++;
			if ($i == $zz_conf['int']['this_limit']) break;
		}
		closedir($folder_handle);

		$data['folders'][$index]['url_self'] = wrap_html_escape($_SERVER['REQUEST_URI']);
		$data['folders'][$index]['total_rows'] = count($files);
		if (!empty($_GET['q'])) $data['folders'][$index]['total_rows'] = $total_files_q;
		$data['folders'][$index]['total_records'] = zz_list_total_records($data['folders'][$index]['total_rows']);
		$data['folders'][$index]['pages'] = zz_list_pages($zz_conf['limit'], $zz_conf['int']['this_limit'], $data['folders'][$index]['total_rows']);
		$zz_conf['search_form_always'] = true;
		$searchform = zz_search_form([], '', $data['folders'][$index]['total_rows'], $data['folders'][$index]['total_rows']);
		$data['folders'][$index]['searchform'] = $searchform['bottom'];
	}

	$page['text'] = wrap_template('maintenance-folders', $data);
	if (!empty($_GET['folder'])) {
		$page['text'] .= wrap_template('zzform-foot', $zz_setting);
	}
	return $page;
}

/**
 * checks if 'search' is active, then tests string against search string
 *
 * @param string $string
 * @return bool false: no search active or string not found; true: search is
 *		active and string was found
 */
function zz_maintenance_searched($string) {
	global $zz_conf;
	if (empty($_GET['q'])) return false;
	if (stristr($string, $_GET['q'])) return true;
	if (stristr(urldecode($string), $_GET['q'])) return true;

	// allow for searching ignoring replaced zero width space
	// PHPs char does not support unicode
	$q = urlencode($_GET['q']);
	if ($zz_conf['character_set'] === 'utf-8') {
		$char8203 = '%E2%80%8B';
	} else {
		// this does not work for all other character sets
		// but it works for iso-8859-1 and -2
		$char8203 = '%26%238203%3B';
	}
	$q = str_replace($char8203, '', $q);
	$q = urldecode($q);
	$_GET['q'] = $q;
	if (stristr($string, $q)) return true;
	return false;
}

/**
 * HTML output of form with button to delete all lines, files etc. in list
 * 'q'-search filter will be regarded
 *
 * @global array $zz_conf
 * @return array
 */
function zz_maintenance_deleteall_form() {
	global $zz_conf;
	if (!empty($_POST['deleteall'])) return ['', ''];
	if (!isset($_GET['deleteall'])) return ['', ''];

	$unwanted_keys = ['deleteall'];
	$qs = zz_edit_query_string($zz_conf['int']['url']['qs_zzform'], $unwanted_keys);
	$url = $zz_conf['int']['url']['full'].$qs;
	return [$url, !empty($_GET['q']) ? wrap_html_escape($_GET['q']) : ''];
}

function zz_maintenance_folders_deleteall($my_folder, $file) {
	if (!empty($_GET['q'])) {
		if (zz_maintenance_searched($file)) {
			$success = unlink($my_folder.'/'.$file);
			return $success;
		}
	} else {
		$success = unlink($my_folder.'/'.$file);
		return $success;
	}
}

/**
 * show settings for error and further logging
 *
 * @return array
 */
function zz_maintenance_errors() {
	global $zz_conf;

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
	$lines[23]['td'] = !empty($zz_conf['upload_log']) ? '<a href="?log='.urlencode(realpath($zz_conf['upload_log']))
				.'">'.realpath($zz_conf['upload_log']).'</a>' : wrap_text('disabled');

	foreach ($lines as $index => $line) {
		if (!$line['td']) $line['td'] = false;
		$lines[$index]['class'] = !empty($line['class']) ? $line['class'].' block480a' : 'block480';
		$lines[$index]['td_class'] = $index & 1 ? 'uneven' : 'even';
		$lines[$index]['explanation'] = !empty($line['explanation'][$line['td']]) ? $line['explanation'][$line['td']] : false;
	}
	return $lines;
}

/**
 * initialize variables and include files to use zz_list() for maintenance
 *
 */
function zz_maintenance_list_init() {
	global $zz_conf;

	// zz_edit_query_string(), zz_get_url_self()
	require_once $zz_conf['dir_inc'].'/functions.inc.php';
	// zz_init_limit()
	require_once $zz_conf['dir_inc'].'/output.inc.php';
	// zz_mark_search_string(), zz_list_total_records(), zz_list_pages()
	require_once $zz_conf['dir_inc'].'/list.inc.php';
	// zz_search_form()
	require_once $zz_conf['dir_inc'].'/search.inc.php';

	$zz_conf['list_display'] = 'table';
	if (empty($zz_conf['limit_all_max']))
		$zz_conf['limit_all_max'] 		= 1500;
	// range in which links to records around current selection will be shown
	if (empty($zz_conf['limit_show_range']))
		$zz_conf['limit_show_range'] 	= 800;
	if (empty($zz_conf['limit_display']))
		$zz_conf['limit_display']		= 'pages';
	$zz_conf['search'] = true;

	$zz_conf['int']['show_list'] = true;
	$zz_conf['int']['url'] = zz_get_url_self(false);
	zz_init_limit();
}

/**
 * output of logfile per line or grouped with the possibility to delete lines
 *
 * @global array $zz_conf
 * @return string HTML output
 */
function zz_maintenance_logs($page) {
	global $zz_conf;
	global $zz_setting;
	require_once $zz_setting['core'].'/file.inc.php';

	zz_maintenance_list_init();

	$page['title'] .= ' '.wrap_text('Logs');
	$page['breadcrumbs'][] = wrap_text('Logs');
	$page['query_strings'] = [
		'filter', 'log', 'limit', 'q', 'scope', 'deleteall'
	];

	$levels = ['error', 'warning', 'notice'];
	if (empty($_GET['log'])) {
		$page['text'] = '<p>'.wrap_text('No logfile specified').'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	$show_log = false;
	foreach ($levels as $level) {
		if ($_GET['log'] === realpath($zz_conf['error_log'][$level])) {
			$show_log = true;
		}
	}
	if ($_GET['log'] === realpath(ini_get('error_log'))) {
		$show_log = true;
	}
	if (!empty($zz_conf['upload_log']) AND $_GET['log'] === realpath($zz_conf['upload_log'])) {
		$show_log = true;
	}
	if (!$show_log) {
		$page['text'] = '<p>'.sprintf(wrap_text('This is not one of the used logfiles: %s'), wrap_html_escape($_GET['log'])).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}
	if (!file_exists($_GET['log'])) {
		$page['text'] = '<p>'.sprintf(wrap_text('Logfile does not exist: %s'), wrap_html_escape($_GET['log'])).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	// delete
	$data['message'] = false;
	if (!empty($_POST['line'])) {
		$data['message'] = wrap_file_delete_line($_GET['log'], $_POST['line']);
	}

	$filters['type'] = ['PHP', 'zzform', 'zzwrap'];
	$filters['level'] = [
		'Notice', 'Deprecated', 'Warning', 'Error', 'Parse error',
		'Strict error', 'Fatal error'
	];
	$filters['group'] = ['Group entries'];
	$f_output = [];
	
	$data['log'] = wrap_html_escape($_GET['log']);

	parse_str($zz_conf['int']['url']['qs_zzform'], $my_query);
	$filters_set = (!empty($my_query['filter']) ? $my_query['filter'] : []);
	$unwanted_keys = ['filter', 'limit'];
	$my_uri = $zz_conf['int']['url']['self'].zz_edit_query_string($zz_conf['int']['url']['qs_zzform'], $unwanted_keys);
	
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
	if ($f_output) {
		$f_output = array_values($f_output);
		$data['filter'] = wrap_template('zzform-list-filter', $f_output);
	}

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
	} else 
		$data['group'] = false;

	list($data['deleteall_url'], $data['deleteall_filter']) = zz_maintenance_deleteall_form();
	if ($data['deleteall_url']) {
		$page['text'] = wrap_template('maintenance-logs', $data);
		return $page;
	}

	// get lines
	$j = 0;
	$delete = [];
	$content = '';
	$dont_highlight_levels = ['Notice', 'Deprecated', 'Warning', 'Upload'];
	$data['lines'] = [];
	$log = [];
	$handle = fopen($_GET['log'], 'r');
	$max_len = $zz_conf['log_errors_max_len'] + 2;
	if ($zz_conf['character_set'] === 'utf-8') {
		// in case of a switch of the character encoding, it can be necessary
		// to increase the maximum length
		// this is not precise (characters may be longer than 2 bytes)
		// but should be sufficient as most characters in error logs are ASCII
		// anyways
		$max_len *= 2;
	}
	if ($handle) {
		$data['total_rows'] = 0;
		$index = 0;
		$i = 0;
		$write_log = true;
		while (($line = fgets($handle, $max_len)) !== false) {
			$line = trim($line);

			if (!empty($_GET['q']) AND !zz_maintenance_searched($line)) {
				$index++;
				continue;
			}

			$error = [];
			$error['type'] = '';
			$error['user'] = '';
			$error['date'] = '';
			$error['level'] = '';
			$error['time'] = '';

			// get date
			if (substr($line, 0, 1) === '[' AND $rightborder = strpos($line, ']')) {
				$error['date'] = substr($line, 1, $rightborder - 1);
				$line = substr($line, $rightborder + 2);
			}
			// get user
			if (substr($line, -1) === ']' AND strstr($line, '[')) {
				$error['user'] = substr($line, strrpos($line, '[')+1, -1);
				$error['user'] = explode(' ', $error['user']);
				if (count($error['user']) > 1 AND substr($error['user'][0], -1) === ':') {
					array_shift($error['user']); // get rid of User: or translations of it
				}
				$error['user'] = implode(' ', $error['user']);
				$line = substr($line, 0, strrpos($line, '['));
			}

			$tokens = explode(' ', $line);
			if ($tokens AND in_array($tokens[0], $filters['type'])) {
				$error['type'] = array_shift($tokens);
				$error['level'] = array_shift($tokens);
				if (substr($error['level'], -1) === ':') $error['level'] = substr($error['level'], 0, -1);
				else $error['level'] .= ' '.array_shift($tokens);
				if (substr($error['level'], -1) === ':') $error['level'] = substr($error['level'], 0, -1);
			}

			if (!empty($_GET['filter'])) {
				if (!empty($_GET['filter']['type'])) {
					if ($error['type'] != $_GET['filter']['type']) {
						$index++;
						continue;
					}
				}
				if (!empty($_GET['filter']['level'])) {
					if ($error['level'] != $_GET['filter']['level']) {
						$index++;
						continue;
					}
				}
			}
			if (in_array($error['type'], ['zzform', 'zzwrap'])) {
				if (!$error['user'])
					$error['user'] = array_pop($tokens);
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
					$error['time'] = substr($time, 1, -1);
					// shorten time to make it more readable
					$error['time'] = substr($error['time'], 0, 6);
				}
			}

			$error['status'] = false;
			if ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[0], -1) === ']') {
				$error['link'] = array_shift($tokens);
				$error['link'] = substr($error['link'], 1, -1);
				if (intval($error['link'])."" === $error['link']) {
					// e. g. 404 has no link repeated as it's already in the
					// error message	
					$error['status'] = $error['link'];
					$error['link'] = false;
				}
			} elseif ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[1], -1) === ']'
				AND strlen($tokens[0]) === 4) {
				$error['status'] = array_shift($tokens);
				$error['status'] = substr($error['status'], 1);
				$error['link'] = array_shift($tokens);
				$error['link'] = substr($error['link'], 0, -1);
			} else {
				$error['link'] = false;
			}
			$error['error'] = implode(' ', $tokens);
			
			if (!empty($_POST['deleteall'])) {
				$delete[] = $index;
			} elseif (!$data['group']) {
				if ($i < ($zz_conf['int']['this_limit'] - $zz_conf['limit'])) {
					$index++;
					$data['total_rows']++;
					$i++;
					continue;
				}
				if ($write_log) {
					$error['index'] = $index;
					$log[$index] = $error;
					$i++;
				}
				if ($zz_conf['int']['this_limit']
					AND $i >= $zz_conf['int']['this_limit']) $write_log = false;
				$data['total_rows']++;
			} else {
				if (empty($log[$error['error']])) {
					$log[$error['error']] = [
						'date_begin' => $error['date'],
						'type' => $error['type'],
						'level' => $error['level'],
						'error' => $error['error'],
						'user' => [$error['user']],
						'index' => [$index],
						'link' => [$error['link']],
						'status' => [$error['status']],
						'time' => [$error['time']]
					];
					$data['total_rows']++;
				} else {
					$log[$error['error']]['index'][] = $index;
					$log[$error['error']]['date_end'] = $error['date'];
					$fields = ['user', 'link', 'status'];
					foreach ($fields as $field) {
						if (!in_array($error[$field], $log[$error['error']][$field]))
							$log[$error['error']][$field][] = $error[$field];
					}
				}
			}
			$index++;
		}
		fclose($handle);
	}
	if ($data['group']) {
		if ($zz_conf['int']['this_limit']) {
			$log = array_slice($log, ($zz_conf['int']['this_limit'] - $zz_conf['limit']), $zz_conf['limit']);
		}
	}

	if (!empty($_POST['deleteall'])) {
		$data['message'] .= wrap_file_delete_line($_GET['log'], $delete);
	}

	// output lines
	foreach ($log as $index => $line) {
		if ($line['level'] AND !in_array($line['level'], $dont_highlight_levels))
			$line['level_highlight'] = true;

		$post = false;
		if (substr($line['error'], 0, 5) === 'POST ') {
			$post = @unserialize(substr($line['error'], 5));
			if ($post)
				$line['error'] = 'POST '.wrap_print($post);
		} elseif (substr($line['error'], 0, 11) === 'POST[json] ') {
			$post = @json_decode(substr($line['error'], 11));
			if ($post)
				$line['error'] = 'POST '.wrap_print($post);
		}
		if (!$post) {
			$no_html = false;
			if (in_array($line['type'], ['zzform', 'zzwrap']))
				$no_html = true;
			$line['error'] = zz_maintenance_splits($line['error'], $no_html);
		}
		// htmlify links
		if (stristr($line['error'], 'http:/&#8203;/&#8203;') OR stristr($line['error'], 'https:/&#8203;/&#8203;')) {
			$line['error'] = preg_replace_callback('~(\S+):/&#8203;/&#8203;(\S+)~', 'zz_maintenance_make_url', $line['error']);
		}
		$line['error'] = str_replace(',', ', ', $line['error']);
		$line['error'] = zz_mark_search_string($line['error']);

		if (!$data['group']) {
			$line['no'] = $index;
			$line['keys'] = $index;
			$line['date_begin'] = $line['date'];
			$line['links'] = ($line['link'] ? '[<a href="'.str_replace('&', '&amp;', $line['link']).'">'
					.zz_maintenance_splits($line['link'], true).'</a>]<br>' : '');
		} else {
			$line['no'] = $j;
			$line['keys'] = implode(',', $line['index']);
			$line['count'] = count($line['index']);
			$line['date_end'] = (!empty($line['date_end']) AND $line['date_end'] !== $line['date_begin']) ? $line['date_end'] : '';
			$line['status'] = implode(' ', $line['status']);
			$line['user'] = implode(', ', $line['user']);
			$line['links'] = '';
			if ($line['link']) {
				foreach ($line['link'] as $link) {
					if (!$link) continue;
					$line['links'] .= '[<a href="'.str_replace('&', '&amp;', $link).'">'.zz_maintenance_splits($link, true).'</a>]<br>';
				}
			}
			$line['time'] = implode(', ', $line['time']);
		}
		$data['lines'][] = $line;
		$j++;
	}

	$data['url_self'] = wrap_html_escape($_SERVER['REQUEST_URI']);
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($zz_conf['limit'], $zz_conf['int']['this_limit'], $data['total_rows']);
	$zz_conf['search_form_always'] = true;
	$searchform = zz_search_form([], '', $data['total_rows'], $data['total_rows']);
	$data['searchform'] = $searchform['bottom'];

	$page['text'] = wrap_template('maintenance-logs', $data);
	$page['text'] .= wrap_template('zzform-foot', $zz_setting);
	return $page;
}

/**
 * get rid of long lines with zero width space (&#8203;) - &shy; does
 * not work at least in firefox 3.6 with slashes
 *
 * @param string $string
 * @param bool $no_html
 * @return string
 */
function zz_maintenance_splits($string, $no_html) {
	$string = str_replace(';', ';&#8203;', $string);
	$string = str_replace('&', '&#8203;&amp;', $string);
	$string = str_replace('&amp;#8203;', '&#8203;', $string);
	$string = str_replace('/', '/&#8203;', $string);
	$string = str_replace('=', '=&#8203;', $string);
	$string = str_replace('%', '&#8203;%', $string);
	$string = str_replace('-at-', '&#8203;-at-', $string);
	if ($no_html) {
		$string = str_replace('<', '&lt;', $string);
	}
	return $string;
}

function zz_maintenance_make_url($array) {
	$href = str_replace('&#8203;', '', $array[0]);
	$linktext = $array[0];
	$link = '<a href="'.$href.'">'.$linktext.'</a>'; 
	return $link;
}

/**
 * export SQL log as JSON file
 *
 * @param array $page
 * @global int $_GET['sqldownload']
 * @return array $page
 */
function zz_maintenance_sqldownload($page) {
	global $zz_conf;
	
	$page['query_strings'][] = 'sqldownload';

	$sql = 'SELECT * FROM %s WHERE log_id >= %d ORDER BY log_id';
	$sql = sprintf($sql, $zz_conf['logging_table'], $_GET['sqldownload']);
	$data = wrap_db_fetch($sql, 'log_id');
	if (!$data) {
		$sql = 'SELECT MAX(log_id) FROM %s';
		$sql = sprintf($sql, $zz_conf['logging_table']);
		$max_logs = wrap_db_fetch($sql, '', 'single value');
		$page['title'] .= ' '.wrap_text('Download SQL log');
		$page['breadcrumbs'][] = wrap_text('Download SQL log');
		$page['text'] = '<p>'.sprintf(wrap_text('Logfile has only %d entries.'), $max_logs).'</p>';
		return mod_default_maintenance_return($page);
	}

	$page['text'] = json_encode($data);
	$page['content_type'] = 'json';
	$page['headers']['filename'] = sprintf('logging_%d.json', $_GET['sqldownload']);
	return $page;
}

/**
 * export JSON file in _logging
 *
 * @param array $page
 * @return array $page
 */
function zz_maintenance_sqlupload($page) {
	global $zz_conf;

	$page['title'] .= ' '.wrap_text('Upload SQL log');
	$page['breadcrumbs'][] = wrap_text('Upload SQL log');
	if (empty($_FILES['sqlfile'])) {
		$page['text'] = '<p>'.wrap_text('Please upload a file.').'</p>';
		return mod_default_maintenance_return($page);
	}
	if ($_FILES['sqlfile']['error'] !== 0) {
		$page['text'] = '<p>'.wrap_text('There was an error while uploading the file.').'</p>';
		return mod_default_maintenance_return($page);
	}
	if ($_FILES['sqlfile']['size'] <= 3) {
		$page['text'] = '<p>'.wrap_text('There was an error while uploading the file.').'</p>';
		return mod_default_maintenance_return($page);
	}
	$json = file_get_contents($_FILES['sqlfile']['tmp_name']);
	$json = json_decode($json, true);
	if (!$json) {
		$page['text'] = '<p>'.wrap_text('The content of the file was not readable (Format needs to be JSON).').'</p>';
		return mod_default_maintenance_return($page);
	}
	$first_id = key($json);
	$sql = 'SELECT MAX(log_id) FROM %s';
	$sql = sprintf($sql, $zz_conf['logging_table']);
	$max_logs = wrap_db_fetch($sql, '', 'single value');
	if ($max_logs + 1 !== $first_id) {
		$page['text'] = '<p>'.sprintf(wrap_text('The highest existing log entry is %d, but import starts with %d.'), $max_logs, $first_id).'</p>';
		return mod_default_maintenance_return($page);
	}
	
	// Everything ok, we can import
	$log_template = 'INSERT INTO %s (query, record_id, user, last_update) VALUES (_binary "%s", %s, "%s", "%s")';
	foreach ($json as $line) {
		$success = wrap_db_query($line['query']);
		if (empty($success['id']) AND empty($success['rows'])) {
			$page['text'] = '<p>'.sprintf(wrap_text('There was an error adding record ID %d.'), $line['log_id']).'</p>';
			return mod_default_maintenance_return($page);
		}
		$sql = sprintf($log_template,
			$zz_conf['logging_table'], wrap_db_escape($line['query'])
			, ($line['record_id'] ? $line['record_id'] : 'NULL')
			, wrap_db_escape($line['user']), $line['last_update']
		);
		$log_id = wrap_db_query($sql);
		if (empty($log_id['id'])) {
			$page['text'] = '<p>'.sprintf(wrap_text('There was an error adding record ID %d.'), $line['log_id']).'</p>';
			return mod_default_maintenance_return($page);
		}
		if ($line['log_id'].'' !== $log_id['id'].'') {
			$page['text'] = '<p>'.sprintf(wrap_text('Record ID %d was added with a different log ID %d.'), $line['log_id'], $log_id['id']).'</p>';
			return mod_default_maintenance_return($page);
		}
	}
	$page['text'] = '<p>'.sprintf(wrap_text('All %d log entries were added, last ID was %d.'), count($json), $line['log_id']).'</p>';
	return mod_default_maintenance_return($page);
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

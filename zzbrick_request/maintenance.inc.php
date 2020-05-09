<?php 

/**
 * default module
 * Maintenance script for database operations with zzform
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2020 Gustaf Mossakowski
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
	$zz_conf['generate_output'] = true; // allow translations in zzform
	$zz_setting['extra_http_headers'][] = 'X-Frame-Options: Deny';
	$zz_setting['extra_http_headers'][] = "Content-Security-Policy: frame-ancestors 'self'";

	if (file_exists($file = $zz_setting['custom'].'/zzbrick_tables/_common.inc.php')) {
		// e. g. heading_prefix
		require_once $file;
	}
	if (isset($brick['page'])) $page = $brick['page'];
	$page['head'] = isset($page['head']) ? $page['head'] : '';
	$page['head'] .= wrap_template('zzform-head');
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
	} elseif (isset($_POST['serversync'])) {
		return zz_maintenance_serversync($page);
	} elseif (isset($_GET['filetree'])) {
		return zz_maintenance_filetree($page);
	} elseif (!empty($_GET['folder'])) {
		return zz_maintenance_folders($page);
	} elseif (!empty($_GET['log'])) {
		return zz_maintenance_logs($page);
	} elseif (isset($_GET['maillog'])) {
		return zz_maintenance_maillogs($page);
	} elseif (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif (isset($_GET['integrity'])) {
		return zz_maintenance_integrity($page);
	} elseif (isset($_GET['dbupdate'])) {
		$newpage = brick_format('%%% make dbupdate %%%');
		$page['title'] .= ' '.$newpage['title'];
		$page['text'] = $newpage['text'];
		$page['breadcrumbs'] = array_merge($page['breadcrumbs'], $newpage['breadcrumbs']);
		$page['query_strings'] = ['dbupdate'];
		return $page;
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
	case 'SET':
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
	foreach ($databases as $db) {
		$db_list[] = [
			'db' => $db,
			'prefered' => $db === $zz_conf['db_name'] ? true : false
		];
	}

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
			AND NOT ISNULL(detail_table.`%s`)
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
		if (!empty($_GET['limit']) AND $_GET['limit'] === 'last') {
			zz_list_limit_last(count($files));
		}
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
		$page['text'] .= wrap_template('zzform-foot');
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
	$lines[23]['td'] = !empty($zz_conf['upload_log']) ? '<a href="?log='.urlencode(realpath($zz_conf['upload_log']))
				.'">'.realpath($zz_conf['upload_log']).'</a>' : wrap_text('disabled');

	$lines[24]['th'] = wrap_text('Logging (Mail)');
	$lines[24]['td'] = !empty($zz_setting['log_mail']) ? '<a href="?maillog">'.$zz_setting['log_dir'].'/mail.log</a>' : wrap_text('disabled');

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

	$logfile = realpath($_GET['log']);
	if (!$logfile) {
		$page['text'] = '<p>'.sprintf(wrap_text('Logfile does not exist: %s'), wrap_html_escape($_GET['log'])).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}
	$data['log'] = wrap_html_escape($logfile);

	$show_log = false;
	foreach ($levels as $level) {
		if ($logfile === realpath($zz_conf['error_log'][$level])) {
			$show_log = true;
		}
	}
	if ($logfile === realpath(ini_get('error_log'))) {
		$show_log = true;
	}
	if (!empty($zz_conf['upload_log']) AND $logfile === realpath($zz_conf['upload_log'])) {
		$show_log = true;
	}
	if (!$show_log) {
		$page['text'] = '<p>'.sprintf(wrap_text('This is not one of the used logfiles: %s'), $data['log']).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	// delete
	$data['message'] = false;
	if (!empty($_POST['line'])) {
		$data['message'] = wrap_file_delete_line($logfile, $_POST['line']);
	}

	$filters['type'] = ['PHP', 'zzform', 'zzwrap'];
	$filters['level'] = [
		'Notice', 'Deprecated', 'Warning', 'Error', 'Parse error',
		'Strict error', 'Fatal error'
	];
	$filters['group'] = ['Group entries'];

	$data['filter'] = mod_default_maintenance_logs_filter($filters);

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
				$found[$line][] = $file->key();
			} else {
				$found[] = $file->key();
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
			$found = array_slice($found, ($zz_conf['int']['this_limit'] - $zz_conf['limit']), $zz_conf['limit']);
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
				$zz_conf['int']['this_limit'] - $zz_conf['limit'],
				($data['total_rows'] < $zz_conf['int']['this_limit'] ? $data['total_rows'] : $zz_conf['int']['this_limit']) - 1
			);
		}
	} else {
		$found = range(0, $data['total_rows']);	
	}

	// output lines
	$j = 0;
	$dont_highlight_levels = ['Notice', 'Deprecated', 'Warning', 'Upload'];
	$data['lines'] = [];

	$log = [];
	if ($data['total_rows']) foreach ($found as $index) {
		$file->seek($index);
		$line = $file->current();
		$line = trim($line);

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
		$error['index'] = $index;

		if ($error['level'] AND !in_array($error['level'], $dont_highlight_levels))
			$error['level_highlight'] = true;

		$post = false;
		if (substr($error['error'], 0, 5) === 'POST ') {
			$post = @unserialize(substr($error['error'], 5));
			if ($post)
				$error['error'] = 'POST '.wrap_print($post);
		} elseif (substr($error['error'], 0, 11) === 'POST[json] ') {
			$post = @json_decode(substr($error['error'], 11));
			if ($post)
				$error['error'] = 'POST '.wrap_print($post);
		}
		if (!$post) {
			$no_html = false;
			if (in_array($error['type'], ['zzform', 'zzwrap']))
				$no_html = true;
			$error['error'] = zz_maintenance_splits($error['error'], $no_html);
		}
		// htmlify links
		if (stristr($error['error'], 'http:/<wbr>/<wbr>') OR stristr($error['error'], 'https:/<wbr>/<wbr>')) {
			$error['error'] = preg_replace_callback('~(\S+):/<wbr>/<wbr>(\S+)~', 'zz_maintenance_make_url', $error['error']);
		}
		$error['error'] = str_replace(',', ', ', $error['error']);
		$error['error'] = zz_mark_search_string($error['error']);

		$error['no'] = $index;
		$error['keys'] = $index;
		$error['date_begin'] = $error['date'];
		$error['links'] = ($error['link'] ? '[<a href="'.str_replace('&', '&amp;', $error['link']).'">'
				.zz_maintenance_splits($error['link'], true).'</a>]<br>' : '');
		$data['lines'][$index] = $error;
		$j++;
	}

	if ($data['group']) {
		$raw = $data['lines'];
		$data['lines'] = [];
		foreach ($group as $gindex => $lines) {
			if (count($lines) === 1) {
				$raw[$lines[0]]['count'] = 1;
				$data['lines'][] = $raw[$lines[0]];
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
			$data['lines'][] = [
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
	}

	$data['url_self'] = wrap_html_escape($_SERVER['REQUEST_URI']);
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($zz_conf['limit'], $zz_conf['int']['this_limit'], $data['total_rows']);
	$zz_conf['search_form_always'] = true;
	$searchform = zz_search_form([], '', $data['total_rows'], $data['total_rows']);
	$data['searchform'] = $searchform['bottom'];

	$page['text'] = wrap_template('maintenance-logs', $data);
	$page['text'] .= wrap_template('zzform-foot');
	return $page;
}

/**
 * output of mail log
 *
 * @global array $zz_conf
 * @return string HTML output
 */
function zz_maintenance_maillogs($page) {
	global $zz_conf;
	global $zz_setting;
	require_once $zz_setting['core'].'/file.inc.php';

	zz_maintenance_list_init();

	$page['title'] .= ' '.wrap_text('Mail Logs');
	$page['breadcrumbs'][] = wrap_text('Mail Logs');
	$page['query_strings'] = [
		'maillog'
	];
	$logfile = $zz_setting['log_dir'].'/mail.log';
	if (!file_exists($logfile)) {
		$page['text'] = '<p>'.sprintf(wrap_text('Logfile does not exist: %s'), wrap_html_escape($logfile)).'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	$file = new \SplFileObject($logfile, 'r');
	$data = [];
	$data['mails'] = [];
	$index = 0;
	$new_mail = true;
	$mail_head = true;
	while (!$file->eof()) {
		$line = $file->fgets();
		$line = trim($line);
		if ($new_mail) {
			if (!$line) continue;
			$index++;
			$data['mails'][$index] = [];
			$new_mail = false;
			$mail_head = true;
		}
		if ($mail_head AND !$line) $mail_head = false;
		if ($mail_head) {
			$key = substr($line, 0, strpos($line, ':'));
			$value = substr($line, strpos($line, ':') + 2);
		} else {
			$key = 'msg';
			$value = $line;	
		}
		if (array_key_exists($key, $data['mails'][$index])) {
			$data['mails'][$index][$key] .= "\n".$value;
		} else {
			$data['mails'][$index][$key] = $value;
		}
		if (array_key_exists('msg', $data['mails'][$index])) {
			if (substr($data['mails'][$index]['msg'], -77) === str_repeat('-', 77)) {
				$new_mail = true;
				$data['mails'][$index]['msg'] = trim(substr($data['mails'][$index]['msg'], 0, -77));
			}
		}
	}

	$page['text'] = wrap_template('maintenance-maillogs', $data);
	return $page;
}

/**
 * output filters for log files
 *
 * @param array $filter
 * @global array $zz_conf
 * @return string
 */
function mod_default_maintenance_logs_filter($filters) {
	global $zz_conf;
	$f_output = [];

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
	if (!$f_output) return '';
	$f_output = array_values($f_output);
	return wrap_template('zzform-list-filter', $f_output);
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
 * export SQL log as JSON file
 *
 * @param array $page
 * @global int $_GET['sqldownload']
 * @return array $page
 */
function zz_maintenance_sqldownload($page) {
	global $zz_conf;
	
	$page['query_strings'][] = 'sqldownload';
	$limit = false;

	list($data, $limit) = mod_default_maintenance_read_logging($_GET['sqldownload']);
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
	if ($limit) {
		$page['headers']['filename'] = sprintf('logging_%d-%d.json', $_GET['sqldownload'], $_GET['sqldownload'] + $limit - 1);
	} else {
		$page['headers']['filename'] = sprintf('logging_%d.json', $_GET['sqldownload']);
	}
	return $page;
}

/*
 * read logging entries from logging table
 *
 * @param int $start
 * @return array
 */
function mod_default_maintenance_read_logging($start) {
	global $zz_conf;
	$limit = 0;

	$sql = 'SELECT COUNT(log_id) FROM %s WHERE log_id >= %d ORDER BY log_id';
	$sql = sprintf($sql, $zz_conf['logging_table'], $start);
	$logcount = wrap_db_fetch($sql, '', 'single value');
	if ($logcount > 10000) {
		$limit = 10000;
	}

	$sql = 'SELECT * FROM %s WHERE log_id >= %d ORDER BY log_id';
	$sql = sprintf($sql, $zz_conf['logging_table'], $start);
	if ($limit) $sql .= sprintf(' LIMIT %d', 10000);
	$data = wrap_db_fetch($sql, 'log_id');
	return [$data, $limit];
}

/**
 * export JSON file in _logging
 *
 * @param array $page
 * @return array $page
 */
function zz_maintenance_sqlupload($page) {
	global $zz_conf;

	$out = [];
	if (empty($_FILES['sqlfile'])) $out['no_file'] = true;
	elseif ($_FILES['sqlfile']['error'] === UPLOAD_ERR_NO_FILE) $out['no_file'] = true;
	elseif ($_FILES['sqlfile']['error'] !== 0) $out['file_error'] = true;
	elseif ($_FILES['sqlfile']['size'] <= 3) $out['file_error'] = true;
	else {
		$json = file_get_contents($_FILES['sqlfile']['tmp_name']);
		$out = mod_default_maintenance_add_logging($json);
	}
	$page['title'] .= ' '.wrap_text('Upload SQL log');
	$page['breadcrumbs'][] = wrap_text('Upload SQL log');
	$page['text'] = wrap_template('maintenance-add-logging', $out);
	return $page;
}

/*
 * add logging entries to logging table
 *
 * @param string $json
 * @return array
 */
function mod_default_maintenance_add_logging($json) {
	global $zz_conf;
	$json = json_decode($json, true);
	if (!$json) return ['no_json' => 1];

	$first_id = key($json);
	$sql = 'SELECT MAX(log_id) FROM %s';
	$sql = sprintf($sql, $zz_conf['logging_table']);
	$max_logs = wrap_db_fetch($sql, '', 'single value');
	if ($max_logs + 1 !== $first_id) {
		return ['max_logs' => $max_logs, 'first_id' => $first_id];
	}
	
	// Everything ok, we can import
	$log_template = 'INSERT INTO %s (query, record_id, user, last_update) VALUES (_binary "%s", %s, "%s", "%s")';
	foreach ($json as $line) {
		$success = wrap_db_query($line['query']);
		if (empty($success['id']) AND empty($success['rows']) AND $success !== true) {
			return ['log_id' => $line['log_id'], 'add_error' => 1];
		}
		$sql = sprintf($log_template,
			$zz_conf['logging_table'], wrap_db_escape($line['query'])
			, ($line['record_id'] ? $line['record_id'] : 'NULL')
			, wrap_db_escape($line['user']), $line['last_update']
		);
		$log_id = wrap_db_query($sql);
		if (empty($log_id['id'])) {
			return ['log_id' => $line['log_id'], 'log_add_error' => 1];
		}
		if ($line['log_id'].'' !== $log_id['id'].'') {
			return ['log_id' => $line['log_id'], 'local_log_id' => $log_id['id']];
		}
	}
	return ['log_id' => $line['log_id'], 'total_count' => count($json)];
}

function zz_maintenance_serversync($page) {
	global $zz_setting;
	
	$page['title'] .= ' '.wrap_text('Synchronize local and remote server');
	$sync_user = wrap_get_setting('sync_user');
	
	if (!$zz_setting['local_access']) {
		$out['local_only'] = true;
		$page['text'] = wrap_template('maintenance-sync-server', $out);
		return $page;
	}

	$path = wrap_get_setting('sync_server_url');
	$url = sprintf('https://%s%s', substr($zz_setting['hostname'], 0, -6), $path);
	$data = ['return_last_logging_entry' => 1];
	list($status, $headers, $content) = wrap_get_protected_url($url, [], 'POST', $data, $sync_user);
	
	if ($status !== 200) {
		$out['status_error'] = $status;
		$page['text'] = wrap_template('maintenance-sync-server', $out);
		return $page;
	}
	$last_log = json_decode($content, true);
	$last_log_local = mod_default_maintenance_last_log();
	if ($last_log === $last_log_local) {
		$out['identical'] = wrap_number($last_log['log_id']);
	} elseif ($last_log['log_id'] < $last_log_local['log_id']) {
		// push data from local server
		list($log, $limit) = mod_default_maintenance_read_logging($last_log['log_id'] + 1);
		$data = [];
		$data['add_log'] = json_encode($log, true);
		list($status, $headers, $content) = wrap_get_protected_url($url, [], 'POST', $data, $sync_user);
		if ($status !== 200) {
			$out['status_error'] = $status;
			$page['text'] = wrap_template('maintenance-sync-server', $out);
			return $page;
		}
		$out = json_decode($content, true);
		$out['hide_upload_form'] = true;
		$out['remote_changes'] = true;
		$page['text'] = wrap_template('maintenance-add-logging', $out);
		return $page;
	} elseif ($last_log['log_id'] > $last_log_local['log_id']) {
		// get data from remote server
		$url .= sprintf('?get_log_from_id=%d', $last_log_local['log_id'] + 1);
		list($status, $headers, $content) = wrap_get_protected_url($url, [], 'GET', [], $sync_user);
		if ($status !== 200) {
			$out['status_error'] = $status;
			$page['text'] = wrap_template('maintenance-sync-server', $out);
			return $page;
		}
		$out = mod_default_maintenance_add_logging($content);
		$out['hide_upload_form'] = true;
		$out['local_changes'] = true;
		$page['text'] = wrap_template('maintenance-add-logging', $out);
		return $page;
	} else {
		$out['mismatch'] = $last_log['log_id'];
	}
	$page['text'] = wrap_template('maintenance-sync-server', $out);
	return $page;
}

function mod_default_maintenance_last_log() {
	global $zz_conf;

	$sql = 'SELECT * FROM %s ORDER BY log_id DESC LIMIT 1';
	$sql = sprintf($sql, $zz_conf['logging_table']);
	$data = wrap_db_fetch($sql);
	return $data;
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

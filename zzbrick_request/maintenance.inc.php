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
	} elseif (isset($_GET['maillog'])) {
		return zz_maintenance_maillogs($page);
	} elseif (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif (isset($_GET['integrity'])) {
		return zz_maintenance_integrity($page);
	} elseif ($type = zz_maintenance_keycheck()) {
		$newpage = brick_format('%%% '.$type['verb'].' '.$type['key'].' %%%');
		$page['title'] .= ' '.$newpage['title'];
		$page['text'] = $newpage['text'];
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
		'dbupdate' => 'make',
		'dbmodules' => 'make',
		'translationscheck' => 'make',
		'cachedircheck' => 'make',
		'toolinfo' => 'request',
		'log' => 'make'
	];
	foreach ($keys as $key => $verb) {
		if (isset($_GET[$key])) return ['key' => $key, 'verb' => $verb];
	}
	return '';
}

/**
 * change the database with a custom SQL query which is logged
 *
 * @param array $page
 * @return array
 */
function zz_maintenance_sqlquery($page) {
	// zz_log_sql()
	wrap_include('database', 'zzform');
	
	wrap_setting('log_username_default', 'Maintenance robot 812');

	$result = [];
	$sql = $_POST['sql'];
	$statement = wrap_sql_statement($sql);

	if (in_array($statement, [
		'INSERT', 'UPDATE', 'DELETE', 'CREATE TABLE', 'ALTER TABLE', 'CREATE VIEW',
		'ALTER VIEW', 'SET'
	])) {
		$success = wrap_db_query($sql, 0);
		if ($success AND in_array($statement, ['INSERT', 'UPDATE', 'DELETE']) AND !$success['rows']) {
			$result['action_nothing'] = true;
		} elseif ($success) {
			zz_log_sql($sql, '', $success['id'] ?? NULL);
			$result['action'] = wrap_text(ucfirst(strtolower($statement)));
		} else {
			$warnings = wrap_db_warnings('list');
			if ($warnings) {
				$result['error_db_msg'] = $warnings[0]['Message'];
				$result['error_db_errno'] = $warnings[0]['Code'];
			} else {
				$result['error_db_msg'] = wrap_text('Unknown error.');
			}
		}
		$result['change'] = true;
	} else {
		$result['not_supported'] = true;
		$result['token'] = wrap_html_escape($statement);
	}
		
	$result['sql'] = zz_maintenance_sql($sql);
	$result['form_sql'] = str_replace('%%%', '%&shy;%&shy;%', wrap_html_escape($sql));

	$page['title'] .= ' '.wrap_text('SQL Query');
	$page['breadcrumbs'][]['title'] = wrap_text('SQL Query');
	$page['text'] = wrap_template('maintenance-sql', $result);
	return $page;
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
 * checks all fields that have an entry in the relations_table if they
 * contain invalid values (e. g. values that do not have a corresponding value
 * in the master table
 *
 * @param array $page
 * @return string text output
 * @todo add translations with wrap_text()
 */
function zz_maintenance_integrity($page) {
	$page['title'] .= ' '.wrap_text('Relational Integrity');
	$page['breadcrumbs'][]['title'] = wrap_text('Relational Integrity');
	$page['query_strings'][] = 'integrity';

	$sql = 'SELECT * FROM %s';
	$sql = sprintf($sql, wrap_sql_table('zzform_relations'));
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
			$results[] = '<li class="error">'.wrap_text('Error').' – '
				.wrap_text('Field %s contains invalid values:'
					, ['values' => '<code>'.$detail_field.'</code>']
				).' ('
				.$relation['detail_id_field'].' => '.$relation['detail_field'].')<br>';
			$line = '';
			foreach ($ids as $id => $foreign_id) {
				$line .= $id.' => '.$foreign_id.'; ';
			}
			$line .= '</li>';
			$results[] = $line;
		} else {
			$results[] = '<li class="ok">'.wrap_text('OK').' – '
				.wrap_text('Field %s contains only valid values.'
					, ['values' => '<code>'.$detail_field.'</code>']
				).'</li>';
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
	$page['title'] .= ' '.wrap_text('Filetree');
	$page['breadcrumbs'][]['title'] = wrap_text('Filetree');
	$page['query_strings'][] = 'filetree';

	$files = [];
	$topdir = $_SERVER['DOCUMENT_ROOT'].'/../';
	$base = false;
	if (!empty($_GET['filetree'])) {
		$files['parts'] = [];
		$parts = explode('/', $_GET['filetree']);
		$text = array_pop($parts);
		$files['parts'][] = ['title' => wrap_html_escape($text)];
		while ($parts) {
			$folder = implode('/', $parts);
			$part = array_pop($parts);
			$files['parts'][] = [
				'title' => wrap_html_escape($part),
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
	
	$data = [];
	if ($page) {
		$page['title'] .= ' '.wrap_text('Backup folder');
		$page['breadcrumbs'][]['title'] = wrap_text('Backup folder');
		$page['query_strings'] = [
			'folder', 'file', 'q', 'scope', 'deleteall', 'limit'
		];
		$data['folder'] = true;
		zz_maintenance_list_init();
	}

	if ((!wrap_setting('zzform_backup') OR !wrap_setting('zzform_backup_dir'))
		AND !wrap_setting('tmp_dir') AND !wrap_setting('cache_dir')) {
		$page['text'] = '<p>'.wrap_text('Backup of uploaded files is not active.').'</p>'."\n";
		return mod_default_maintenance_return($page);
	}

	$dirs = [
		'TEMP' => wrap_setting('tmp_dir'),
		'BACKUP' => wrap_setting('zzform_backup_dir'),
		'CACHE' => wrap_setting('cache_dir')
	];
	$data['folders'] = [];
	foreach ($dirs as $key => $dir) {
		$exists = file_exists($dir) ? true : false;
		$data['folders'][] = [
			'title' => $key,
			'hide_content' => true,
			'not_exists' => !$exists AND $dir ? true: false,
			'dir' => realpath($dir)
		];
		if (!$exists) continue;
		if (substr($dir, -1) === '/') $dir = substr($dir, 0, -1);
		if (!empty($_GET['folder']) AND substr($_GET['folder'], 0, strlen($key)) === $key) {
			$my_folder = $dir.substr($_GET['folder'], strlen($key));
		}
	}

	if (!empty($_GET['folder']) AND !empty($_GET['file'])) {
		$file['name'] = $my_folder.'/'.$_GET['file'];
		$extension = wrap_file_extension(substr($file['name'], strrpos($file['name'], '%2F')));
		if (strstr($extension, '%3F')) {
			$extension = explode('%3F', $extension);
			$extension = $extension[0];
			$file['ext'] = $extension;
		}
		switch ($extension) {
			case 'headers': $file['ext'] = 'txt'; break;
			case '%2F': $file['ext'] = 'txt'; break; // display HTML sourcecode
		}
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

	foreach ($data['folders'] as $index => $folder) {
		if (empty($_GET['folder'])) continue;
		if (substr($_GET['folder'], 0, strlen($folder['title'])) != $folder['title']) continue;
		$data['folders'][$index]['hide_content'] = false;
		if ($folder['title'] !== $_GET['folder']) {
			$data['folders'][$index]['subtitle'] = wrap_html_escape($_GET['folder']);
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
			if ($i < $zz_conf['int']['this_limit'] - wrap_setting('zzform_limit')) {
				$i++;
				continue;
			}
			$path = $my_folder.'/'.$filename;
			$file = [];
			$file['file'] = $filename;
			$file['size'] = filesize($path);
			$data['folders'][$index]['size_total'] += $file['size'];
			$basename = substr($filename, strrpos($filename, '%2F'));
			$ext = wrap_file_extension($basename);
			if ($pos = strpos($ext, '%3F')) $ext = substr($ext, 0, $pos);
			if (is_dir($path)) {
				$file['ext'] = wrap_text('Folder');
			} elseif ($ext === 'headers') {
				$file['ext'] = 'TXT';
			} elseif ($ext === '%2F') {
				$file['ext'] = 'HTML';
			} elseif ($ext) {
				// treat part behind last dot as file extension
				// normally, file extensions won't be longer than 10 characters
				// not 100% correct of course
				$file['ext'] = strtoupper($ext);
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
		$data['folders'][$index]['pages'] = zz_list_pages($zz_conf['int']['this_limit'], $data['folders'][$index]['total_rows']);
		wrap_setting('zzform_search_form_always', true);
		$searchform = zz_search_form([], '', $data['folders'][$index]['total_rows'], $data['folders'][$index]['total_rows']);
		$data['folders'][$index]['searchform'] = $searchform['bottom'];
	}

	$page['text'] = wrap_template('maintenance-folders', $data);
	if (!empty($_GET['folder']))
		$page['text'] .= wrap_template('zzform-foot');
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
	if (empty($_GET['q'])) return false;
	if (stristr($string, $_GET['q'])) return true;
	if (stristr(urldecode($string), $_GET['q'])) return true;

	// allow for searching ignoring replaced zero width space
	// PHPs char does not support unicode
	$q = urlencode($_GET['q']);
	if (wrap_setting('character_set') === 'utf-8') {
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
 * initialize variables and include files to use zz_list() for maintenance
 *
 */
function zz_maintenance_list_init() {
	global $zz_conf;
	static $init = false;
	if ($init) return; // just once

	// zz_edit_query_string(), zz_get_url_self()
	wrap_include('functions', 'zzform');
	// zz_init_limit()
	wrap_include('output', 'zzform');
	// zz_mark_search_string(), zz_list_total_records(), zz_list_pages()
	wrap_include('list', 'zzform');
	// zz_search_form()
	wrap_include('searchform', 'zzform');

	wrap_setting('zzform_search', 'bottom');

	$zz_conf['int']['url'] = zz_get_url_self();
	zz_init_limit();
	$init = true;
}


/**
 * format a single line from log
 *
 * @param string $line
 * @param array $types (optional)
 * @return array
 */
function zz_maintenance_logs_line($line, $types = []) {
	zz_maintenance_list_init();
	
	$dont_highlight_levels = ['Notice', 'Deprecated', 'Warning', 'Upload'];

	$out = [];
	$out['type'] = '';
	$out['user'] = '';
	$out['date'] = '';
	$out['level'] = '';
	$out['time'] = '';
	$out['status'] = false;

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

	if ($out['level'] AND !in_array($out['level'], $dont_highlight_levels))
		$out['level_highlight'] = true;

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
	require_once wrap_setting('core').'/file.inc.php';

	zz_maintenance_list_init();

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
 * export SQL log as JSON file
 *
 * @param array $page
 * @global int $_GET['sqldownload']
 * @return array $page
 */
function zz_maintenance_sqldownload($page) {
	wrap_include('logging', 'zzform');
	$page['query_strings'][] = 'sqldownload';
	$limit = false;

	list($data, $limit) = zz_logging_read($_GET['sqldownload']);
	if (!$data) {
		$max_log_id = zz_logging_max();
		$page['title'] .= ' '.wrap_text('Download SQL log');
		$page['breadcrumbs'][]['title'] = wrap_text('Download SQL log');
		$page['text'] = '<p>'.wrap_text('Logfile has only %d entries.', ['values' => $max_log_id]).'</p>';
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

/**
 * export JSON file in _logging
 *
 * @param array $page
 * @return array $page
 */
function zz_maintenance_sqlupload($page) {
	$out = [];
	if (empty($_FILES['sqlfile'])) $out['no_file'] = true;
	elseif ($_FILES['sqlfile']['error'] === UPLOAD_ERR_NO_FILE) $out['no_file'] = true;
	elseif ($_FILES['sqlfile']['error'] !== 0) $out['file_error'] = true;
	elseif ($_FILES['sqlfile']['size'] <= 3) $out['file_error'] = true;
	else {
		wrap_include('logging', 'zzform');
		$json = file_get_contents($_FILES['sqlfile']['tmp_name']);
		$out = zz_logging_add($json);
	}
	$page['title'] .= ' '.wrap_text('Upload SQL log');
	$page['breadcrumbs'][]['title'] = wrap_text('Upload SQL log');
	$page['text'] = wrap_template('maintenance-add-logging', $out);
	return $page;
}

function zz_maintenance_serversync($page) {
	wrap_include('logging', 'zzform');
	$page['title'] .= ' '.wrap_text('Synchronize local and remote server');
	wrap_setting('log_username_default', wrap_setting('sync_user'));
	
	if (!wrap_setting('local_access')) {
		$out['local_only'] = true;
		$page['text'] = wrap_template('maintenance-sync-server', $out);
		return $page;
	}

	$path = wrap_path('default_sync_server');
	$url = sprintf('https://%s%s', substr(wrap_setting('hostname'), 0, -6), $path);
	$data = ['return_last_logging_entry' => 1];
	$headers_to_send[] = 'Accept: application/json';
	list($status, $headers, $content) = wrap_get_protected_url($url, $headers_to_send, 'POST', $data);
	
	if ($status !== 200) {
		$out['status_error'] = $status;
		$content = json_decode($content, true);
		$out['error_explanation'] = $content['error_explanation'] ?? '';
		$page['text'] = wrap_template('maintenance-sync-server', $out);
		return $page;
	}
	$last_log = json_decode($content, true);
	$last_log_local = zz_logging_last();
	if ($last_log === $last_log_local) {
		$out['identical'] = wrap_number($last_log['log_id']);
	} elseif ($last_log['log_id'] < $last_log_local['log_id']) {
		// push data from local server
		list($log, $limit) = zz_logging_read($last_log['log_id'] + 1);
		$data = [];
		$data['add_log'] = json_encode($log, true);
		list($status, $headers, $content) = wrap_get_protected_url($url, $headers_to_send, 'POST', $data);
		if ($status !== 200) {
			$out['status_error'] = $status;
			$content = json_decode($content, true);
			$out['error_explanation'] = $content['error_explanation'] ?? '';
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
		list($status, $headers, $content) = wrap_get_protected_url($url, $headers_to_send);
		if ($status !== 200) {
			$out['status_error'] = $status;
			$content = json_decode($content, true);
			$out['error_explanation'] = $content['error_explanation'] ?? '';
			$page['text'] = wrap_template('maintenance-sync-server', $out);
			return $page;
		}
		$out = zz_logging_add($content);
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

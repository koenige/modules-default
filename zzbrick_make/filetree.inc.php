<?php 

/**
 * default module
 * show filetree
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show filetree
 *
 * @param array $page
 * @return array
 */
function mod_default_make_filetree($params) {
	$data = [];
	$base = false;
	if ($params) {
		if (strstr($params[0], '/')) $params = explode('/', $params[0]);
		$data['parts'] = [];
		$parts = $params;
		$text = array_pop($parts);
		$data['parts'][] = ['title' => wrap_html_escape($text)];
		while ($parts) {
			$folder = implode('/', $parts);
			$part = array_pop($parts);
			$data['parts'][] = [
				'title' => wrap_html_escape($part),
				'link' => $folder
			];
		}
		$data['parts'] = array_reverse($data['parts']);
		$base = implode('/', $params).'/';
	}
	if ($params AND in_array(wrap_setting('cms_dir').'/'.$params[0], mod_default_filetree_special_folders())) {
		$data = array_merge($data, mod_default_filetree_folders($params));
		if (!empty($data['folder_inexistent']))
			$page['status'] = 404;
		$page['query_strings'] = [
			'folder', 'file', 'q', 'scope', 'deleteall', 'limit'
		];
		$page['text'] = wrap_template('filetree', $data);
	} else {
		$data += mod_default_filetree_files(wrap_setting('cms_dir').'/'.$base, $base);
		$page['text'] = wrap_template('filetree-simple', $data);
	}

	$page['title'] = wrap_text('Filetree');
	$page['breadcrumbs'][]['title'] = wrap_text('Filetree');
	return $page;
}

/**
 * show files in a directory, directory links to sub directories
 *
 * @param string $dir
 * @param string $base
 * @return string
 */
function mod_default_filetree_files($dir, $base) {
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
			list ($size, $files_in_folder) = mod_default_filetree_dirsize($path);
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
 * send a file
 *
 * @param array $params
 * @return void
 */
function mod_default_filetree_file($params) {
	$file['name'] = implode('/', $params);
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

/**
 * get file extension per file for filetree
 *
 * treat part behind last dot as file extension
 * normally, file extensions won't be longer than 10 characters
 * not 100% correct of course
 * @param string $filename
 * @return string
 */
function mod_default_filetree_file_ext($filename) {
	// cache folder: filename is escaped, still get extension of cached ressource
	if (strstr($filename, '%2F')) $filename = urldecode($filename);
	$basename = basename($filename);
	$ext = wrap_file_extension($basename);
	if ($ext AND $pos = strpos($ext, '?')) $ext = substr($ext, 0, $pos);
	if ($ext === 'headers') return 'TXT';
	if (substr($basename, 0, 1) === '?') return 'HTML';
	if (substr($filename, -1) === '/') return 'HTML';
	if ($ext) return strtoupper($ext);
	return wrap_text('unknown');
}

/**
 * get size of directory and files inside, recursively
 *
 * @param string $dir absolute path of directory
 * @return array
 */
function mod_default_filetree_dirsize($dir) {
	$size = 0;
	$files = 0;
	$handle = opendir($dir);
	if (!$handle) return [$size, $files];
	while ($file = readdir($handle)) {
		if ($file === '.' OR $file === '..') continue;
		if (is_dir($dir.'/'.$file)) {
			list ($mysize, $myfiles) = mod_default_filetree_dirsize($dir.'/'.$file);
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
 * delete files
 *
 * @return int no. of deleted files
 */
function mod_default_filetree_delete($my_folder) {
	$deleted = 0;
	if (!$my_folder) return $deleted;
	if (empty($_POST['files'])) return $deleted;
	foreach ($_POST['files'] as $file => $bool) {
		if ($bool != 'on') continue;
		$filename = sprintf('%s/%s', $my_folder, $file);
		if (file_exists($filename)) {
			if (is_dir($filename)) {
				rmdir($filename);
				$deleted++;
			} else {
				unlink($filename);
				$deleted++;
			}
		}
	}
	return $deleted;
}

/**
 * show special folders in maintenance overview
 *
 * @return array
 */
function mod_default_filetree_list_special() {
	if ((!wrap_setting('zzform_backup') OR !wrap_setting('zzform_backup_dir'))
		AND !wrap_setting('tmp_dir') AND !wrap_setting('cache_dir')) {
		$page['text'] = '<div id="zzform" class="maintenance"><p>'.wrap_text('Backup of uploaded files is not active.').'</p></div>'."\n";
		return $page;
	}

	$folders = mod_default_filetree_special_folders();

	foreach ($folders as $key => $dir) {
		$exists = file_exists($dir) ? true : false;
		$dir = realpath($dir);
		$data['folders'][] = [
			'title' => $key,
			'not_exists' => !$exists AND $dir ? true: false,
			'dir' => realpath($dir),
			'link' => str_starts_with($dir, wrap_setting('cms_dir')) ? substr($dir, strlen(wrap_setting('cms_dir')) + 1) : NULL
		];
	}
	$page['text'] = wrap_template('filetree-folders', $data);
	return $page;
}

/**
 * list of folders that are treated specially
 *
 * @return array
 */
function mod_default_filetree_special_folders() {
	static $folders = [];
	if (!$folders) {
		$folders = [
			'TEMP' => wrap_setting('tmp_dir'),
			'BACKUP' => wrap_setting('zzform_backup_dir'),
			'CACHE' => wrap_setting('cache_dir')
		];
	}
	return $folders;
}

function mod_default_filetree_folders($params) {
	global $zz_conf;

	$data = [];
	if (!$params) return $data;
	$my_folder = sprintf('%s/%s', wrap_setting('cms_dir'), implode('/', $params));
	if (!is_dir($my_folder)) {
		$data['folder_inexistent'] = true;
		return $data;
	}
	zzform_list_init();

	if (!empty($_GET['file']))
		return mod_default_filetree_file([$my_folder, $_GET['file']]);

	$data['deleted'] = mod_default_filetree_delete($my_folder);

	$folder_handle = opendir($my_folder);

	$files = [];
	$total_files_q = 0;
	while ($file = readdir($folder_handle)) {
		if (substr($file, 0, 1) === '.') continue;
		if (!empty($_POST['deleteall'])) {
			$data['deleted'] += mod_default_filetree_folders_deleteall($my_folder, $file);
			continue;
		}
		$files[] = $file;
		if (mf_default_searched($file)) {
			$total_files_q++;
		}
	}
	if (!$data['deleted']) $data['deleted'] = NULL;
	sort($files);

	list($data['deleteall_url'], $data['deleteall_filter']) = mf_default_delete_all_form();
	if ($data['deleteall_url']) return $data;

	$i = 0;
	$data['size_total'] = 0;
	$data['files_total'] = 0;
	if (!empty($_GET['limit']) AND $_GET['limit'] === 'last') {
		zz_list_limit_last(count($files));
	}
	foreach ($files as $filename) {
		if (!empty($_GET['q']) AND !mf_default_searched($filename)) {
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
		$data['size_total'] += $file['size'];
		$file['ext'] = is_dir($path) ? wrap_text('Folder') : mod_default_filetree_file_ext($filename);
		$file['time'] = date('Y-m-d H:i:s', filemtime($path));
		$file['files_in_dir'] = 0;
		if (is_dir($path)) {
			$file['dir'] = true;
			$file['link'] = urlencode(implode('/', $params)).'/'.urlencode($filename);
			$subfolder_handle = opendir($path);
			while ($subdir = readdir($subfolder_handle)) {
				if (substr($subdir, 0, 1) === '.') continue;
				$file['files_in_dir']++;
			}
			closedir($subfolder_handle);
		} else {
			$file['link'] = urlencode(implode('/', $params)).'&amp;file='.urlencode($filename);
		}
		if (!$file['files_in_dir']) $file['files_in_dir'] = NULL;
		$file['title'] = zz_mark_search_string(str_replace('%', '%&shy;', wrap_html_escape(urldecode($filename))));
		$data['files'][] = $file;
		$i++;
		$data['files_total']++;
		if ($i == $zz_conf['int']['this_limit']) break;
	}
	closedir($folder_handle);

	$data['url_self'] = wrap_html_escape($_SERVER['REQUEST_URI']);
	$data['total_rows'] = count($files);
	if (!empty($_GET['q'])) $data['total_rows'] = $total_files_q;
	$data['total_records'] = zz_list_total_records($data['total_rows']);
	$data['pages'] = zz_list_pages($zz_conf['int']['this_limit'], $data['total_rows']);
	wrap_setting('zzform_search_form_always', true);
	$searchform = zz_search_form([], '', $data['total_rows'], $data['total_rows']);
	$data['searchform'] = $searchform['bottom'];

	return $data;
}

function mod_default_filetree_folders_deleteall($my_folder, $file) {
	if (!empty($_GET['q'])) {
		if (mf_default_searched($file)) {
			$success = unlink($my_folder.'/'.$file);
			return $success;
		}
	} else {
		$success = unlink($my_folder.'/'.$file);
		return $success;
	}
}

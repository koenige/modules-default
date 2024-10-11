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
	$files = [];
	$base = false;
	if ($params) {
		if (strstr($params[0], '/')) $params = explode('/', $params[0]);
		$files['parts'] = [];
		$parts = $params;
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
		$base = implode('/', $params).'/';
	}
	$files += mod_default_filetree_files(wrap_setting('cms_dir').'/'.$base, $base);

	$page['title'] = wrap_text('Filetree');
	$page['breadcrumbs'][]['title'] = wrap_text('Filetree');
	$page['text'] = wrap_template('filetree-simple', $files);
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
		zzform_list_init();
	}

	if ((!wrap_setting('zzform_backup') OR !wrap_setting('zzform_backup_dir'))
		AND !wrap_setting('tmp_dir') AND !wrap_setting('cache_dir')) {
		$page['text'] = '<div id="zzform" class="maintenance"><p>'.wrap_text('Backup of uploaded files is not active.').'</p></div'."\n";
		return $page;
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
			if (mf_default_searched($file)) {
				$total_files_q++;
			}
		}
		sort($files);
		if ($deleted) {
			$data['folders'][$index]['deleted'] = $deleted;
		}

		list($data['folders'][$index]['deleteall_url'], $data['folders'][$index]['deleteall_filter']) = mf_default_delete_all_form();
		if ($data['folders'][$index]['deleteall_url']) {
			$page['text'] = wrap_template('filetree', $data);
			return $page;
		}

		$i = 0;
		$data['folders'][$index]['size_total'] = 0;
		$data['folders'][$index]['files_total'] = 0;
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

	$page['text'] = wrap_template('filetree', $data);
	if (!empty($_GET['folder']))
		$page['text'] .= wrap_template('zzform-foot');
	return $page;
}

function zz_maintenance_folders_deleteall($my_folder, $file) {
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

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
	$page['text'] = wrap_template('filetree', $files);
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

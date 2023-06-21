<?php 

/**
 * default module
 * check cache directories
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check cache directories
 *
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_cachedircheck($params) {
	if (!wrap_setting('cache')) return false;
	
	$data['active_cache_folder'] = wrap_setting('cache_dir_zz');
	if (wrap_setting('cache_directories')) {
		$data['inactive_cache_folder'] = substr(wrap_setting('cache_dir_zz'), 0, -2);
		$data['inactive_cache_folder_dir'] = '';
	} else {
		$data['inactive_cache_folder'] = wrap_setting('cache_dir_zz').'/d';
		$data['inactive_cache_folder_dir'] = '/d';
	}
	
	$keys = ['active', 'inactive'];
	foreach ($keys as $key) {
		$data[$key.'_folders'] = scandir($data[$key.'_cache_folder']);
		foreach ($data[$key.'_folders'] as $index => $folder) {
			$keep = true;
			if (str_starts_with($folder, '.')) $keep = false;
			if (!is_dir($data[$key.'_cache_folder'].'/'.$folder)) $keep = false;
			if ($folder === 'd') $keep = false;
			if (!$keep) {
				unset($data[$key.'_folders'][$index]);
				continue;
			}
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$data['counter'] = 0;
		foreach ($data['inactive_folders'] as $folder) {
			$data['counter'] += mod_default_make_cachedircheck_folders($data['inactive_cache_folder_dir'], $folder, $data['counter']);
		}
	}
	
	$page['text'] = wrap_template('cachedircheck', $data);
	$page['title'] = wrap_text('Check Cache Directories');
	$page['breadcrumbs'][]['title']= wrap_text('Check Cache Directories');
	return $page;
}

function mod_default_make_cachedircheck_folders($path, $folder, $counter) {
	// not using scandir() here because number of files might be too big
	$handle = opendir(wrap_setting('cache_dir').$path.'/'.$folder);
	$break = false;
	while (($filename = readdir($handle)) !== false) {
		if (str_starts_with($filename, '.')) continue;
		if (str_starts_with($filename, '%2F')) {
			$new_filename = explode('%2F', $filename);
			array_shift($new_filename);
			$last = array_pop($new_filename);
			if (str_starts_with($last, '%3F')) {
				$last = 'index'.$last;
			} elseif (!$last) {
				$last = 'index';
			} elseif ($last === '.headers') {
				$last = 'index.headers';
			}
			$new_filename[] = $last;
			$new_filename = implode('/', $new_filename);
		} else {
			// @todo never tested if this works (and if there is a need for it)
			$new_filename = urlencode($filename);
		}
		$old_path = wrap_setting('cache_dir').$path.'/'.$folder.'/'.$filename;
		$new_path = wrap_setting('cache_dir_zz').$path.'/'.$folder.'/'.$new_filename;
		if (file_exists($new_path)) {
			unlink($old_path);
			$counter++;
			continue;
		}
		// file exists? left over from older days
		$new_dir = dirname($new_path);
		if (file_exists($new_dir) AND !is_dir($new_dir)) rmdir($new_dir);
		wrap_mkdir($new_dir);
		$success = rename($old_path, $new_path);
		$counter++;
		if ($counter > 100000) {
			$break = true;
			break;
		}
	}
	if (!$break) unlink(wrap_setting('cache_dir').$path.'/'.$folder);
	return $counter;
}

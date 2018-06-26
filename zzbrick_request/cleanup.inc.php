<?php

/**
 * default module
 * Cleanup script
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2018 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_cleanup() {
	global $zz_setting;
	
	// Sessions
	$data['session_cleanup'] = mod_default_session_cleanup();
	
	// Folders via settings
	if (!empty($zz_setting['cleanup_folders'])) {
		foreach ($zz_setting['cleanup_folders'] as $folder) {
			$folder['folder'] = realpath($folder['folder']);
			if (!$folder['folder']) {
				wrap_error(sprintf('Folder to clean up does not exist: %s', $folder['folder']));
				continue;
			}
			$data['folders'][] = [
				'folder' => $folder['folder'],
				'max_age_seconds' => $folder['max_age_seconds'],
				'deleted_files' => mod_default_file_cleanup($folder['folder'], $folder['max_age_seconds'])
			];
		}
	}
	
	$page['text'] = wrap_template('cleanup', $data);
	return $page;
}

/**
 * deletion of session cookie fiels if someone did not log out properly after
 * the maximum lifetime of a session
 *
 * @return int
 */
function mod_default_session_cleanup() {
	$counter = 0;
	global $zz_setting;
	if (empty($zz_setting['session_save_path'])) return $counter;
	if (!is_dir($zz_setting['session_save_path'])) return $counter;
	$files = scandir($zz_setting['session_save_path']);
	if (!$files) return $counter;
	$invalid = time() - $zz_setting['logout_inactive_after'] * 60;
	foreach ($files as $file) {
		if (substr($file, 0, 1) === '.') continue;
		$filename = $zz_setting['session_save_path'].'/'.$file;
		if (filemtime($filename) < $invalid) {
			unlink($filename);
			$counter++;
		}
	}
	return $counter;
}

/**
 * delete files (and subfolders) inside a folder after a given time
 *
 * @param string $folder full path to folder without trailing slash
 * @param int $max_age_seconds maximum allowed age in seconds
 * @return int $counter number of deleted files
 */
function mod_default_file_cleanup($folder, $max_age_seconds) {
	if (!is_dir($folder)) {
		wrap_error(sprintf('Cleanup for folder %s not possible: folder does not exist.', $folder));
		return $counter;
	}
	$invalid = time() - $max_age_seconds;
	$counter = mod_default_file_cleanup_recursive($folder, $invalid);
	return $counter;
}

/**
 * delete files and folders recursively after a given time
 *
 * @param string $folder full path to folder without trailing slash
 * @param int $invalid timestamp after which file/folder should be deleted
 * @param bool $delete_folder (optional) delete own folder if empty yes/no?
 * @return int $counter number of deleted files
 */
function mod_default_file_cleanup_recursive($folder, $invalid, $delete_folder = false) {
	$counter = 0;
	$files = scandir($folder);
	if (!$files) return $counter;
	$filecount = 0;
	foreach ($files as $file) {
		if (in_array($file, ['.', '..'])) continue;
		if (substr($file, 0, 1) === '.') {
			// hidden file, do not delete, do not delete folder
			$delete_folder = false;
			continue;
		}
		$filecount++;
		$filename = $folder.'/'.$file;
		if (is_dir($filename)) {
			$counter += mod_default_file_cleanup_recursive($filename, $invalid, true);
		} elseif (filemtime($filename) < $invalid) {
			unlink($filename);
			$counter++;
			$filecount--;
		}
	}
	// delete empty folders after the same timespan to avoid race conditions
	// (other process might just have created this folder)
	// not 100% working, because just in the cleanup moment this folder might be
	// needed
	if (!$filecount AND $delete_folder AND filemtime($folder) < $invalid) rmdir($folder);
	return $counter;	
}

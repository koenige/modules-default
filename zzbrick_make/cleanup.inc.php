<?php

/**
 * default module
 * Cleanup script
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2018, 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_make_cleanup() {
	global $zz_setting;
	
	// Sessions
	$data['session_cleanup'] = mod_default_session_cleanup();
	
	// Folders via settings
	if (!empty($zz_setting['cleanup_folders'])) {
		foreach ($zz_setting['cleanup_folders'] as $folder) {
			$folder['folder_real'] = realpath($folder['folder']);
			if (!$folder['folder_real']) {
				wrap_error(sprintf('Folder to clean up does not exist: %s', $folder['folder']));
				continue;
			}
			$data['folders'][] = [
				'folder' => $folder['folder_real'],
				'max_age_seconds' => $folder['max_age_seconds'],
				'deleted_files' => mod_default_file_cleanup($folder['folder'], $folder['max_age_seconds'])
			];
		}
	}
	
	// Compress old logfiles
	$data['http_log_compression'] = mod_default_make_cleanup_gzip_logs();
	
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

/**
 * compress old http log files with gzip
 *
 * @return int
 */
function mod_default_make_cleanup_gzip_logs() {
	global $zz_setting;

	$counter = 0;
	if (empty($zz_setting['http_log'])) return $counter;

	$dir = sprintf('%s/access', $zz_setting['log_dir']);
	$years = scandir($dir);
	foreach ($years as $year) {
		if (substr($year, 0, 1) === '.') continue;
		$year_dir = sprintf('%s/%s', $dir, $year);
		if (!is_dir($year_dir)) continue;
		$months = scandir($year_dir);
		foreach ($months as $month) {
			if (substr($month, 0, 1) === '.') continue;
			$month_dir = sprintf('%s/%s/%s', $dir, $year, $month);
			$logfiles = scandir($month_dir);
			foreach ($logfiles as $logfile) {
				if (substr($logfile, -4) !== '.log') continue;
				if ($year == date('Y') AND $month == date('m')) continue;
				$logfile_path = sprintf('%s/%s', $month_dir, $logfile);
				$gzip_logfile_path = $logfile_path.'.gz';
				if (!strstr(ini_get('disable_functions'), 'exec')) {
					// gzip preserves timestamp
					$command = sprintf('gzip -N -9 %s %s', $logfile_path, $gzip_logfile_path);
					exec($command);
				} else {
					$time = filemtime($logfile_path);
					copy($logfile_path, 'compress.zlib://'.$gzip_logfile_path);
					if (!file_exists($gzip_logfile_path)) continue;
					touch($gzip_logfile_path, $time);
					unlink($logfile_path);
				}
				$counter++;
			}
		}
	}
	return $counter;
}

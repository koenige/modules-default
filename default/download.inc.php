<?php

/**
 * default module
 * download functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * create a ZIP archive from a list of files
 *
 * @param array $files_to_zip list of files, with for each file:
 *   string 'local_filename'
 *   string 'filename'
 * @param string $dl_file file name of ZIP archive
 * @return array
 */
function mf_default_download_zip($files_to_zip, $dl_file) {
	// Temporary folder, so we do not mess this ZIP with other file downloads
	ignore_user_abort(1); // make sure we can delete temporary files at the end
	$temp_folder = rand().time();
	mkdir(wrap_setting('tmp_dir').'/'.$temp_folder);
	$zip_file = wrap_setting('tmp_dir').'/'.$temp_folder.'/'.$dl_file;

	$download_zip_mode = wrap_setting('download_zip_mode');
	if (!$download_zip_mode OR $download_zip_mode === 'shell') {
		$success = mod_mediadb_download_zip_shell($zip_file, $files_to_zip, wrap_setting('tmp_dir').'/'.$temp_folder);
	} else {
		$success = mod_mediadb_download_zip_php($zip_file, $files_to_zip);
	}
	if (!$success) {
		wrap_error('Creation of ZIP file '. $dl_file.' failed', E_USER_ERROR);
		exit;
	}

	$file = [];
	$file['name'] = $zip_file;
	$file['cleanup'] = true; // delete file after downloading
	$file['cleanup_dir'] = wrap_setting('tmp_dir').'/'.$temp_folder; // remove folder after downloading
	return $file;
}

/**
 * Create ZIP archive from files via shell zip
 * (faster ZIP creation)
 *
 * @param string $zip_file filename
 * @param string $json_file filename of JSONL-file with list of filenames
 *		[n]['filename'] absolute path to file
 *		[n]['local_filename'] relative path for ZIP archive
 * @return bool true: everything ok, false: error
 */
function mod_mediadb_download_zip_shell($zip_file, $files_to_zip, $temp_path) {
	$filelist = [];

	// create hard links to filesystem
	mkdir($temp_path.'/ln');
	chdir($temp_path.'/ln');
	$current_folder = getcwd();
	$created = [];
	foreach ($files_to_zip as $file) {
		$return = wrap_mkdir(dirname($current_folder.'/'.$file['local_filename']));
		if (is_array($return)) $created += $return;
		link(realpath($file['filename']), $current_folder.'/'.$file['local_filename']);
		$filelist[] = $file['local_filename'];
	}

	// zip files
	// -o	make zipfile as old as latest entry
	// -0	store files (no compression)
	$command = 'zip -o -0 %s %s';
	$command = sprintf($command, $zip_file, implode(' ', $filelist));
	exec($command);
	
	// cleanup files, remove hardlinks
	foreach ($files_to_zip as $file) {
		unlink($current_folder.'/'.$file['local_filename']);
	}
	$created = array_reverse($created);
	foreach ($created as $folder) {
		rmdir($folder);
	}
	chdir($temp_path);
	rmdir($temp_path.'/ln');
	return true;
}

/**
 * Create ZIP archive from files with PHP class ZipArchive
 * (if exec() is not available)
 *
 * @param string $zip_file filename
 * @param string $json_file filename of JSONL-file with list of filenames
 *		[n]['filename'] absolute path to file
 *		[n]['local_filename'] relative path for ZIP archive
 * @return bool true: everything ok, false: error
 */
function mod_mediadb_download_zip_php($zip_file, $files_to_zip) {
	$zip = new ZipArchive;
	if ($zip->open($zip_file, ZIPARCHIVE::CREATE) !== TRUE) {
		return false;
	}
	foreach ($files_to_zip as $file) {
		$zip->addFile($file['filename'], $file['local_filename']);
		// @todo maybe check if connection_aborted() but with what as a flush?
	}
	$zip->close();
	return true;
}

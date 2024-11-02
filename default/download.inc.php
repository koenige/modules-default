<?php

/**
 * default module
 * download functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check download restrictions
 * no. of files, size of files
 *
 * @param array $files
 * @param string $backlink link back to page before as second line of heading
 * @param string $filesize_key (optional, key in $files that denotes file size)
 * @return array (empty = everything ok, otherwise, it is an error page)
 */
function mf_default_download_restrictions($files, $backlink = '', $filesize_key = 'filesize') {
	if (!$files)
		wrap_quit(503, wrap_text('None of the files for the requested archive could be found on the server.'));

	$size = 0;
	foreach ($files as $file) $size += $file[$filesize_key];
	
	$data = [];
	if (count($files) > wrap_setting('default_download_max_files'))
		$data['too_many_files'] = count($files);
	elseif ($size > wrap_setting('default_download_max_filesize'))
		$data['size_too_big'] = $size;
	if (!$data) return [];

	$page['title'] = wrap_text('Download: Archive Too Big').$backlink;
	$page['text'] = wrap_template('download-error', $data);
	return $page;
}	

/**
 * create a ZIP archive from a list of files
 *
 * @param array $files list of files, with for each file:
 *   string 'local_filename'
 *   string 'filename'
 * @param string $download_file file name of ZIP archive
 * @return array
 */
function mf_default_download_zip($files, $download_file) {
	// make sure we can delete temporary files at the end
	ignore_user_abort(1);

	// Temporary folder, so we do not mess this ZIP with other file downloads
	$temp_folder = sprintf('%s/%s%s', wrap_setting('tmp_dir'), rand(), time());
	mkdir($temp_folder);
	$zip_file = sprintf('%s/%s.zip', $temp_folder, $download_file);
	$metadata_file = sprintf('%s/Metadata.csv', $temp_folder, $download_file);
	$pointer = fopen($metadata_file, 'w');
	
	// metadata?
	$metadata = false;
	foreach ($files as $file) {
		if (empty($file['meta'])) continue;
		if (!$metadata) {
			$metadata = true;
			fputcsv($pointer, array_keys($file['meta']));
		}
		fputcsv($pointer, $file['meta']);
	}
	fclose($pointer);
	if ($metadata) {
		$files[] = [
			'filename' => $metadata_file,
			'local_filename' => basename($metadata_file)
		];
	}

	switch (wrap_setting('default_download_zip_mode')) {
		case 'php':
			$success = mf_default_download_zip_php($zip_file, $files);
			break;
		case 'shell':
		default:
			$success = mf_default_download_zip_shell($zip_file, $files, $temp_folder);
			break;
	}
	unlink($metadata_file);
	if (!$success) {
		wrap_error(wrap_text('Creation of ZIP file “%s” failed.', ['values' => [$download_file]]), E_USER_ERROR);
		exit;
	}

	$file = [];
	$file['name'] = $zip_file;
	$file['cleanup'] = true; // delete file after downloading
	$file['cleanup_dir'] = $temp_folder; // remove folder after downloading
	return wrap_send_file($file);
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
function mf_default_download_zip_shell($zip_file, $files, $temp_path) {
	$filelist = [];

	// create hard links to filesystem
	mkdir($temp_path.'/ln');
	chdir($temp_path.'/ln');
	$current_folder = getcwd();
	$created = [];
	foreach ($files as $file) {
		$return = wrap_mkdir(dirname($current_folder.'/'.$file['local_filename']));
		if (is_array($return)) $created = array_merge($created, $return);
		link(realpath($file['filename']), $current_folder.'/'.$file['local_filename']);
		$filelist[] = $file['local_filename'];
	}

	// zip files
	// -o	make zipfile as old as latest entry
	// -0	store files (no compression)
	$command = 'zip -o -0 %s "%s"';
	$command = sprintf($command, $zip_file, implode('" "', $filelist));
	exec($command);
	
	// cleanup files, remove hardlinks
	foreach ($files as $file)
		unlink($current_folder.'/'.$file['local_filename']);
	$created = array_reverse($created);
	foreach ($created as $folder)
		rmdir($folder);
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
function mf_default_download_zip_php($zip_file, $files) {
	$zip = new ZipArchive;
	if ($zip->open($zip_file, ZIPARCHIVE::CREATE) !== TRUE) return false;
	foreach ($files as $file)
		$zip->addFile($file['filename'], $file['local_filename']);
		// @todo maybe check if connection_aborted() but with what as a flush?
	$zip->close();
	return true;
}

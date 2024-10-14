<?php 

/**
 * default module
 * show special folders in maintenance view
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show special folders in maintenance overview
 *
 * @return array
 */
function mod_default_specialfolders() {
	if ((!wrap_setting('zzform_backup') OR !wrap_setting('zzform_backup_dir'))
		AND !wrap_setting('tmp_dir') AND !wrap_setting('cache_dir')) {
		$page['text'] = '<div id="zzform" class="maintenance"><p>'.wrap_text('Backup of uploaded files is not active.').'</p></div>'."\n";
		return $page;
	}

	$folders = [
		'TEMP' => wrap_setting('tmp_dir'),
		'BACKUP' => wrap_setting('zzform_backup_dir'),
		'CACHE' => wrap_setting('cache_dir')
	];

	foreach ($folders as $key => $dir) {
		$exists = file_exists($dir) ? true : false;
		$dir = realpath($dir);
		$data['folders'][] = [
			'title' => $key,
			'not_exists' => !$exists AND $dir ? true: false,
			'dir' => realpath($dir),
			'link' => str_starts_with($dir, wrap_setting('default_filetree_dir')) ? substr($dir, strlen(wrap_setting('default_filetree_dir')) + 1) : NULL
		];
	}
	$page['text'] = wrap_template('specialfolders', $data);
	return $page;
}

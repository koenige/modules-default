<?php 

/**
 * default module
 * preview and write help/index.json from help file scan
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 *
 * Variables
 * translate_pot = admin
 */


/**
 * Show help index diff for one package (GET preview, POST write)
 *
 * @param array $params package folder name
 * @return array|false $page
 */
function mod_default_make_helpupdate($params) {
	wrap_access_quit('default_maintenance');

	if (count($params) !== 1) return false;
	$package = $params[0];
	if (!wrap_package($package)) return false;

	$data = mod_default_make_helpupdate_data($package);

	if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST['write'])) {
		if (empty($_POST['package']) OR $_POST['package'] !== $package) {
			$data['package_invalid'] = true;
		} else {
			$return = mf_default_help_write($package, $data['filename']);
			$data = mod_default_make_helpupdate_data($package);
			$data[$return] = true;
		}
	}

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('helpupdate', $data);
	$page['title'] = wrap_text('Update Help Index');
	return $page;
}

/**
 * Data for helpupdate template
 *
 * @param string $package
 * @return array
 */
function mod_default_make_helpupdate_data($package) {
	wrap_include('zzbrick_request_get/help', 'default');
	wrap_include('diff', 'zzwrap');

	$filename_local = 'help/index.json';
	$filename = wrap_package_folder($package).'/'.$filename_local;
	$old = file_exists($filename) ? file_get_contents($filename) : '';
	$entries = mf_default_help_collect($package);
	$new = mf_default_help_json_encode($entries);
	$stats = mf_default_help_diff_stats($old, $entries);

	return [
		'empty' => !count($entries),
		'writeable' => $old !== $new,
		'filename_local' => $filename_local,
		'filename' => $filename,
		'count' => count($entries),
		'diff_html' => wrap_diff($old, $new),
		'added' => $stats['added'] ?: '',
		'deleted' => $stats['deleted'] ?: '',
		'updated' => $stats['updated'] ?: '',
		'package' => $package
	];
}

/**
 * JSON diff stats between existing index and scan
 *
 * @param string $old_content
 * @param array $new_entries
 * @return array added, deleted, updated (int)
 */
function mf_default_help_diff_stats($old_content, array $new_entries) {
	$stats = ['added' => 0, 'deleted' => 0, 'updated' => 0];
	$old = json_decode($old_content, true);
	if (!is_array($old)) $old = [];

	foreach ($new_entries as $identifier => $variants) {
		if (!array_key_exists($identifier, $old))
			$stats['added']++;
		elseif ($old[$identifier] !== $variants)
			$stats['updated']++;
	}
	foreach ($old as $identifier => $variants) {
		if (!array_key_exists($identifier, $new_entries))
			$stats['deleted']++;
	}
	return $stats;
}

/**
 * Write scanned help index
 *
 * @param string $package
 * @param string $filename
 * @return string
 */
function mf_default_help_write($package, $filename) {
	wrap_include('zzbrick_request_get/help', 'default');
	$entries = mf_default_help_collect($package);
	$new = mf_default_help_json_encode($entries);
	$old = file_exists($filename) ? file_get_contents($filename) : '';

	if ($old === $new) return 'file_content_unchanged';
	if (file_put_contents($filename, $new) === false) return 'file_not_writable';
	return 'file_written';
}

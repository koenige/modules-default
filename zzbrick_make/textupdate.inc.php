<?php 

/**
 * default module
 * preview gettext .pot entries from source scan
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Show translatable strings for one package (GET preview)
 *
 * @param array $params package folder name
 * @return array|false $page
 */
function mod_default_make_textupdate($params) {
	wrap_access_quit('default_maintenance');
	wrap_include('language-extract', 'zzwrap');

	if (count($params) !== 1) return false;
	$package = $params[0];
	if ($package !== 'custom' AND !in_array($package, wrap_setting('modules'))) return false;

	$data = mod_default_make_textupdate_data($package);
	$data['package'] = $package;
	$data['write'] = $_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST['write']);

	if ($data['write']) {
		if (empty($_POST['package']) OR $_POST['package'] !== $package) {
			$data['write_done'] = false;
			$data['write_message'] = wrap_text('Invalid package.');
		} else {
			$result = wrap_text_pot_write($package);
			$data = mod_default_make_textupdate_data($package) + [
				'package' => $package,
				'write' => true,
				'write_done' => $result['ok'],
				'write_message' => $result['message'],
			];
		}
	}

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('textupdate', $data);
	$page['title'] = wrap_text('Update Text Files');
	$page['breadcrumbs'][] = ['title' => $page['title']];
	return $page;
}

/**
 * Data for textupdate template: scanned .pot diffs per file
 *
 * @param string $package
 * @return array
 */
function mod_default_make_textupdate_data($package) {
	$data = ['pots' => [], 'empty' => true, 'writeable' => false];

	foreach (wrap_text_pot_items($package) as $pot) {
		$stats = wrap_text_pot_diff_stats($pot['old'], $pot['entries']);
		if (wrap_text_pot_normalize($pot['old']) !== $pot['new'])
			$data['writeable'] = true;
		$data['pots'][] = [
			'filename' => $pot['filename'],
			'diff_html' => wrap_text_pot_diff_html($pot['old'], $pot['new']),
			'count' => count($pot['entries']),
			'added' => $stats['added'] ?: '',
			'deleted' => $stats['deleted'] ?: '',
			'updated' => $stats['updated'] ?: '',
		];
		$data['empty'] = false;
	}
	return $data;
}

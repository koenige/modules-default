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
		$data['write_done'] = false;
		$data['write_message'] = wrap_text('Writing .pot files is not implemented yet.');
	}

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('textupdate', $data);
	$page['title'] = wrap_text('Update Text Files');
	$page['breadcrumbs'][] = ['title' => $page['title']];
	return $page;
}

function mod_default_make_textupdate_data($package) {
	$data = ['pots' => [], 'empty' => true];
	$sources_by_pot = wrap_text_sources_by_pot($package);

	foreach ($sources_by_pot as $pot_suffix => $entries) {
		if (!$entries) continue;

		$pot_file = wrap_text_log_pot_file($package, $pot_suffix);
		$data['pots'][] = [
			'filename' => basename($pot_file),
			'content' => wrap_text_format_pot_chunks($entries),
			'count' => count($entries),
		];
		$data['empty'] = false;
	}
	return $data;
}

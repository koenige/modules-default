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
			wrap_include('zzbrick_request_get/help', 'default');
			wrap_include('index', 'default');
			$return = mf_default_index_write($package, $data['filename'], 'mf_default_help_collect');
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
	wrap_include('index', 'default');
	return mf_default_index_data($package, 'help/index.json', mf_default_help_collect($package));
}

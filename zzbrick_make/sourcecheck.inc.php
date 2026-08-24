<?php 

/**
 * default module
 * overview: JSON indices and .pot files up to date?
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
 * Show index.json and .pot status for all packages
 *
 * @param array $params
 * @return array $page
 */
function mod_default_make_sourcecheck($params) {
	wrap_access_quit('default_maintenance');

	$data = [];
	$data['packages'] = mod_default_sourcecheck_packages();

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('sourcecheck', $data);
	$page['title'] = wrap_text('Help, Tables, and Text Files');
	$page['breadcrumbs'][]['title'] = wrap_text('Help, Tables, and Text Files');
	return $page;
}

/**
 * Packages with help index or .pot status
 *
 * @return array
 */
function mod_default_sourcecheck_packages() {
	wrap_include('zzbrick_request_get/help', 'default');
	wrap_include('zzbrick_request_get/tables', 'default');
	wrap_include('index', 'default');
	$package_names = wrap_setting('modules');
	array_unshift($package_names, 'custom');
	$packages = [];
	foreach ($package_names as $package) {
		$help = mf_default_index_status($package, 'help/index.json', 'mf_default_help_collect');
		$tables = mf_default_tables_index_status($package);
		$pot = mf_default_pot_status($package);
		if ($help === 'empty' AND $tables === 'empty' AND $pot === 'empty') continue;

		$cfg = wrap_cfg_files('package', ['package' => $package, 'translate' => true]);
		$row = [
			'package' => $package,
			'name' => $cfg['about']['name'] ?? $package,
			'help_current' => $help === 'current',
			'help_missing' => $help === 'missing',
			'help_outdated' => $help === 'outdated',
			'tables_current' => $tables === 'current',
			'tables_missing' => $tables === 'missing',
			'tables_outdated' => $tables === 'outdated',
			'pot_current' => $pot === 'current',
			'pot_missing' => $pot === 'missing',
			'pot_outdated' => $pot === 'outdated',
		];
		if ($help !== 'empty')
			$row['helpupdate_url'] = '?helpupdate='.$package;
		if ($tables !== 'empty')
			$row['tableupdate_url'] = '?tableupdate='.$package;
		if ($pot !== 'empty')
			$row['textupdate_url'] = '?textupdate='.$package;
		$packages[] = $row;
	}
	usort($packages, fn($left, $right) => strcmp($left['package'], $right['package']));
	return $packages;
}

/**
 * Are .pot files up to date for one package?
 *
 * @param string $package
 * @return string empty|missing|current|outdated
 */
function mf_default_pot_status($package) {
	wrap_include('pot', 'zzwrap');
	$missing = false;
	$outdated = false;
	$has_pots = false;
	foreach (wrap_pot_items($package) as $pot) {
		$has_pots = true;
		if ($pot['old'] === '') {
			$missing = true;
			continue;
		}
		if (wrap_pot_normalize_for_diff($pot['old']) !== wrap_pot_normalize_for_diff($pot['new']))
			$outdated = true;
	}
	if (!$has_pots) return 'empty';
	if ($missing) return 'missing';
	if ($outdated) return 'outdated';
	return 'current';
}

<?php 

/**
 * default module
 * Help: list of packages with help texts
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * overview of packages with help texts
 *
 * @param array $params
 * @return array|false
 */
function mod_default_help_packages($params) {
	$data = [];
	$data['packages'] = mf_default_help_packages();
	$page['extra']['css'][] = 'default/help.css';
	$page['breadcrumbs'][] = ['title' => wrap_text('Help')];
	$page['title'] = wrap_text('Help');
	$page['text'] = wrap_template('help-packages', $data);
	return $page;
}

/**
 * packages with number of help texts (language-aware, one per identifier)
 *
 * @return array
 */
function mf_default_help_packages() {
	wrap_include('help', 'default');
	$files = mf_default_help_files();
	$names = [];
	foreach ($files as $variants) {
		foreach ($variants as $variant)
			$names[$variant['package']] = true;
	}
	$packages = [];
	foreach (array_keys($names) as $package) {
		$pkg = wrap_cfg_files('package', ['package' => $package, 'translate' => true]);
		$entry = [
			'package' => $package,
			'name' => $pkg['about']['name'] ?? $package,
			'count' => count(mf_default_help_list($package))
		];
		$tagline = trim((string) ($pkg['about']['tagline'] ?? ''));
		if ($tagline) $entry['tagline'] = $tagline;
		$packages[] = $entry;
	}
	usort($packages, function ($a, $b) {
		return strcmp($a['name'], $b['name']);
	});
	return $packages;
}

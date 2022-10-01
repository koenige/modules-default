<?php 

/**
 * default module
 * CSS from all active packages
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * include CSS from all active modules and themes
 *
 * @global array $zz_setting
 * @return string
 */
function page_packagecss() {
	global $zz_setting;

	if (empty($zz_setting['activated'])) return '';
	ksort($zz_setting['activated']); // first modules, then themes
	$activated = $zz_setting['activated'];

	// include CSS from all active modules, but only main theme
	if (!empty($activated['themes']) AND count($activated['themes']) > 1)
		$activated['themes'] = [$activated['themes'][0]];

	$css = [];
	foreach ($activated as $type => $packages) {
		foreach ($packages as $package) {
			// do not include CSS from default module
			if ($package === 'default') continue;
			$file = wrap_collect_files('layout/'.$package.'.css', $package);
			if (!$file) continue;
			$css[]['filename'] = $package;
		}
	}
	return wrap_template('packagecss', $css);
}
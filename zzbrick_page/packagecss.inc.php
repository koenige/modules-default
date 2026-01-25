<?php 

/**
 * default module
 * CSS from all active packages
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * include CSS from all active modules and themes
 *
 * @return string
 */
function page_packagecss($params, $page) {
	global $zz_page;

	$css = [];
	$extra_css = $page['extra']['css'] ?? [];
	if (!is_array($extra_css)) $extra_css = [$extra_css];
	if (!empty($zz_page['db']['live']) AND $zz_page['db']['live'] === 'no') {
		$extra_css[] = 'default/internal';
	} elseif (wrap_session_value('logged_in'))
		$extra_css[] = 'default/internal';
	}
	if ($extra_css) $extra_css = array_unique($extra_css);
	foreach ($extra_css as $line) {
		list($package, $filename) = explode('/', $line);
		if (str_ends_with($filename, '.css'))
			$filename = substr($filename, 0, -4);
		$css[] = [
			'package' => $package,
			'filename' => $filename
		];
	}

	$activated = wrap_setting('activated');
	ksort($activated); // first modules, then themes

	// include CSS from all active modules, but only main theme
	if (!empty($activated['themes']) AND count($activated['themes']) > 1)
		$activated['themes'] = [$activated['themes'][0]];

	foreach ($activated as $type => $packages) {
		if (!$packages) continue; // 400 bad request etc.
		foreach ($packages as $package) {
			// do not include CSS from default module
			if ($package === 'default') continue;
			$file = wrap_collect_files('layout/'.$package.'.css', $package);
			if (!$file) continue;
			$css[] = [
				'package' => $package,
				'filename' => $package
			];
		}
	}
	return wrap_template('packagecss', $css);
}

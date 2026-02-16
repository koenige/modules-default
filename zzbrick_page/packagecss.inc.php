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
	} elseif (wrap_session_value('logged_in')) {
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

	// include CSS from all active modules
	$activated = wrap_setting('activated_modules');
	// … but only main theme (at the end!)
	if (wrap_setting('activated_themes'))
		$activated[] = reset(wrap_setting('activated_themes'));
	
	foreach ($activated as $package) {
		// do not include CSS from default module
		if ($package === 'default') continue;
		$file = wrap_collect_files('layout/'.$package.'.css', $package);
		if (!$file) continue;
		$css[] = [
			'package' => $package,
			'filename' => $package
		];
	}
	return wrap_template('packagecss', $css);
}

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
	if (wrap_page_field('live') === 'no') {
		$extra_css[] = 'default/internal';
	} elseif (wrap_session_value('logged_in')) {
		$extra_css[] = 'default/internal';
	}
	if ($extra_css) $extra_css = array_unique($extra_css);
	foreach ($extra_css as $line) {
		list($package, $filename) = explode('/', $line);
		if (str_ends_with($filename, '.css'))
			$filename = substr($filename, 0, -4);
		$file = wrap_collect_files('layout/'.$filename.'.css', $package);
		if (!$file) continue;
		$css[] = [
			'package' => $package,
			'filename' => $filename,
			'nocache' => mf_default_nocache('css', mf_default_filemtime($file))
		];
	}

	// include CSS from all active modules
	$activated = wrap_setting('activated_modules');
	// … but only main theme (at the end!)
	if (wrap_setting('activated_themes')) {
		$themes = wrap_setting('activated_themes');
		$activated[] = reset($themes);
	}
	
	foreach ($activated as $package) {
		// do not include CSS from default module
		if ($package === 'default') continue;
		$file = wrap_collect_files('layout/'.$package.'.css', $package);
		if (!$file) continue;
		$css[] = [
			'package' => $package,
			'filename' => $package,
			'nocache' => mf_default_nocache('css', mf_default_filemtime($file))
		];
	}
	return wrap_template('packagecss', $css);
}

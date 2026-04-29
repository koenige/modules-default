<?php 

/**
 * default module
 * overview of installed packages (modules)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show list of installed modules (taglines from configuration/package.cfg → [about] tagline)
 *
 * @param array $params
 * @param array $local_settings
 * @param array $data
 * @return array $page 'text', 'title', 'breadcrumbs', …
 */
function mod_default_modules($params, $local_settings = [], $data = []) {
	wrap_access_quit('default_modules');

	$data = [];
	foreach (wrap_setting('modules') as $module) {
		$pkg = wrap_cfg_files('package', ['package' => $module]);
		$tagline = '';
		if (!empty($pkg['about']['tagline'])) {
			$tagline = trim((string) $pkg['about']['tagline']);
		}
		$data['modules'][] = [
			'module' => $module,
			'tagline' => $tagline,
		];
	}

	$page = [];
	$page['text'] = wrap_template('modules', $data);
	$page['title'] = wrap_text('Installed modules');
	$page['breadcrumbs'][]['title'] = wrap_text('Installed modules');
	return $page;
}

<?php 

/**
 * default module
 * detail page for one installed package (module)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Show one module (title + tagline from configuration/package.cfg [about])
 *
 * @param array $params
 * @return array|false $page 'text', 'title', 'breadcrumbs', …
 */
function mod_default_module($params) {
	wrap_access_quit('default_module');

	if (count($params) !== 1) return false;
	$module = $params[0];
	if (!in_array($module, wrap_setting('modules'))) return false;

	$pkg = wrap_cfg_files('package', ['package' => $module]);

	$data = [];
	$data['title'] = $pkg['about']['name'] ?? $module;
	$data['tagline'] = $pkg['about']['tagline'] ?? '';

	$page = [];
	$page['title'] = $data['title'];
	$page['breadcrumbs'][] = ['title' => $data['title']];
	$page['text'] = wrap_template('module', $data);
	return $page;
}

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
 *
 * Variables
 * translate_pot = admin
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

	$pkg = wrap_cfg_files('package', ['package' => $module, 'translate' => true]);

	$data = [];
	$data['module'] = $module;
	$data['title'] = $pkg['about']['name'] ?? $module;
	$data['tagline'] = $pkg['about']['tagline'] ?? '';
	$paths = $pkg['categorytrees']['path'] ?? [];
	if ($paths && !is_array($paths)) {
		$paths = [$paths];
	}
	$data['categorytrees'] = mod_default_module_categorytrees($paths);

	wrap_include('zzbrick_request_get/help', 'default');
	$data['has_help'] = (bool) mf_default_help_list($module);
	if ($data['has_help']) {
		$data['has_help'] = (bool) wrap_path('default_help_package', $module, ['testing' => 1]);
	}

	$page = [];
	$page['title'] = $data['title'];
	$page['breadcrumbs'][] = ['title' => $data['title']];
	$page['text'] = wrap_template('module', $data);
	return $page;
}

/**
 * Category tree links from package.cfg [categorytrees] path[]
 *
 * @param array $paths category root paths from package.cfg [categorytrees] path[]
 * @return array<int, array{path: string, title: string, description?: string}>
 */
function mod_default_module_categorytrees(array $paths): array {
	$trees = [];
	foreach ($paths as $path) {
		$path = trim((string) $path);
		if ($path === '') continue;
		if (!$category_id = wrap_category_id($path)) continue;
		if (!wrap_path('default_categorytree', $path, ['testing' => 1])) continue;
		$trees[$category_id] = [
			'category_id' => $category_id,
			'path' => $path
		];
	}
	if (!$trees) return [];

	$sql = 'SELECT category_id, category, description
		FROM categories WHERE category_id IN (%s)';
	$sql = sprintf($sql, implode(',', array_keys($trees)));
	$categories = wrap_db_fetch($sql, 'category_id');
	$categories = wrap_translate($categories, 'categories');
	
	foreach ($categories as $category_id => $category) {
		$trees[$category_id]['title'] = $category['category'];
		$trees[$category_id]['description']
			= trim((string) ($category['description'] ?? ''));
	}
	usort($trees, fn($a, $b) => strcmp($a['path'], $b['path']));
	return $trees;
}

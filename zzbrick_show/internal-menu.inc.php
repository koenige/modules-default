<?php 

/**
 * default module
 * internal area menu section
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * render one internal area menu section
 *
 * examples:
 * 		%%% show internal-menu help %%%
 * 		%%% show internal-menu website %%%
 *
 * @param array $params
 * @return array|bool
 */
function mod_default_show_internal_menu($params, $settings) {
	if (count($params) !== 1) return false;

	$data = [
		'menu' => $params[0],
	];
	$data['items'] = mf_default_internal_menu_items($data['menu']);
	if (!$data['items']) return false;

	$page['text'] = wrap_template('internal-menu', $data);
	return $page;
}

/**
 * menu items for one internal area group
 *
 * @param string $menu help, website, content, or module
 * @return array list of path, title, description, priority, key
 */
function mf_default_internal_menu_items($menu) {
	wrap_include('help', 'default');
	if (!in_array($menu, mf_default_help_menus(), true)) return [];

	$items = mf_default_internal_menu_from_help($menu);
	$items = array_merge($items, wrap_routes_menu($menu));
	return mf_default_internal_menu_sort($items);
}

/**
 * menu items from help/index.json for one group
 *
 * @param string $menu
 * @return array
 */
function mf_default_internal_menu_from_help($menu) {
	$files = mf_default_help_files();
	$items = [];
	foreach ($files as $identifier => $variants) {
		$matching = [];
		foreach ($variants as $variant) {
			if (($variant['menu'] ?? '') === $menu)
				$matching[] = $variant;
		}
		if (!$matching) continue;

		$variant = mf_default_help_pick_variant($matching);
		if (!$variant) continue;

		$path = wrap_path('default_help', [$variant['package'], $identifier], ['hide_missing' => true]);
		if (!$path) continue;

		$items[] = [
			'path' => $path,
			'title' => $variant['title'],
			'description' => $variant['menu_description'] ?? '',
			'priority' => array_key_exists('menu_priority', $variant)
				? $variant['menu_priority']
				: null,
			'key' => $identifier,
		];
	}
	return $items;
}

/**
 * sort internal menu items by priority then title
 *
 * @param array $items
 * @return array
 */
function mf_default_internal_menu_sort(array $items) {
	usort($items, function ($first, $second) {
		$first_has = $first['priority'] !== null;
		$second_has = $second['priority'] !== null;
		if ($first_has && !$second_has) return -1;
		if (!$first_has && $second_has) return 1;
		if ($first_has && $second_has && $first['priority'] !== $second['priority'])
			return $second['priority'] <=> $first['priority'];
		$title = strcmp($first['title'], $second['title']);
		if ($title !== 0) return $title;
		return strcmp($first['key'], $second['key']);
	});
	return $items;
}

<?php 

/**
 * default module
 * menu images
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * menu images
 *
 * examples:
 * 		%%% show menu-images %%%
 * 		%%% show menu-images current %%% — submenu of the active main-menu section
 * 		%%% show menu-images top %%% — explicit menu bucket (same as default if that is main_menu)
 *
 * @param array $params [0] optional: `current`, or a menu bucket name from nav_db
 * @return array|bool
 */
function mod_default_show_menu_images($params, $settings) {
	wrap_include('nav', 'zzwrap');
	wrap_menu_webpages_register();
	$page = wrap_menu_get([]);
	if (empty($page['nav_db'])) return false;

	// get correct menu (`main_menu`, another bucket name, or `current` submenu)
	if (empty($params[0])) {
		$entries = $page['nav_db'][wrap_setting('main_menu')] ?? [];
	} elseif ($params[0] === 'current') {
		$top_id = wrap_nav_top($page['nav_db']);
		if (!$top_id) return false;
		$menu_name = $page['current_menu'] ?: wrap_setting('main_menu');
		if (empty($page['nav_db'][$menu_name][$top_id])) return false;
		$entries = $page['nav_db'][wrap_menu_id(
			$page['nav_db'][$menu_name][$top_id],
			wrap_sql_fields('page_id')
		)] ?? [];
	} elseif (isset($page['nav_db'][$params[0]])) {
		$entries = $page['nav_db'][$params[0]];
	} else {
		return false;
	}

	$data = [];
	foreach ($entries as $id => $item) {
		if (!is_array($item) || empty($item['url'])) continue;
		$page_id = $item[wrap_sql_fields('page_id')] ?? null;
		if (!$page_id) continue;
		$data[$page_id] = [
			'title' => $item['title'],
			'url' => $item['url'],
		];
	}
	if (!$data) return false;

	// get hero image per page from webpages_media
	$media = wrap_media(array_keys($data), 'webpages');
	foreach (array_keys($data) as $page_id) {
		if (!array_key_exists($page_id, $media)) continue;
		$image = reset($media[$page_id]['images']);
		if (($image['sequence'] ?? '') . '' !== '1') continue;
		$data[$page_id] += $image;
	}
	if (!$data) return false;

	$page['text'] = wrap_template('menu-images', $data);
	return $page;
}

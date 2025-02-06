<?php 

/**
 * default module
 * common functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get category tree hierarchy
 *   array hierarchy, id => level
 *   array categories, id => category_id, main_category_id, category, category_level_0 to n
 *
 * @param string $path
 * @return array
 */
function mf_default_category_hierarchy($path) {	
	$ids = wrap_category_id($path, 'list');
	$sql = 'SELECT category_id, main_category_id, category
		FROM categories
		WHERE category_id IN (%s)
		ORDER BY main_category_id, sequence';
	$sql = sprintf($sql, implode(',', $ids));
	$categories = wrap_db_fetch($sql, 'category_id');
	$categories = wrap_translate($categories, 'categories');

	// get IDs sorted by hierarchy
	$hierarchy = wrap_hierarchy($categories, 'main_category_id', wrap_category_id($path));

	// get categories sorted by hierarchy
	$data = [];
	foreach ($hierarchy as $category_id => $level) {
		$category = $categories[$category_id];
		$category['level'] = $level;
		$category['category_level_'.$level] = $category['category'];
		$main_category = $categories[$category['main_category_id']] ?? [];
		for ($i = $level - 1; $i >= 0; $i--) {
			$category['category_level_'.$i] = $main_category['category'];
			$main_category = $categories[$main_category['main_category_id']] ?? [];
		}
		$data[$category_id] = $category;
	}
	return $data;
}

/**
 * read menu hierarchy from categories
 *
 * @param array $parameter
 * @param string $path
 * @return string
 */
function mf_default_categories_menu_hierarchy($parameter, $path) {
	if (empty($parameter['show_menu_hierarchy'])) return '';
	$path_start = $parameter['show_menu_hierarchy_path_start'] ?? wrap_setting('show_menu_hierarchy_path_start');
	if (!$path_start) return $path;

	$path_start--;
	$path = explode('/', $path);
	while ($path_start) {
		array_shift($path);
		$path_start--;
		if (!$path) break;
	}
	return implode('/', $path);
}

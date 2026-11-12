<?php 

/**
 * default module
 * common functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021-2023, 2025-2026 Gustaf Mossakowski
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

/**
 * texts stored for a category below texts/{group}, in the active language
 *
 * @param int $category_id
 * @param string $group e. g. 'forms' for texts/forms
 * @param string $language (optional) language code, e. g. 'de'; active language if empty
 * @return array text type (identifier under texts/{group}/, alias over path) => text
 */
function mf_default_category_texts($category_id, $group, $language = false) {
	if (!$category_id) return [];
	$prefix = 'texts/'.strtolower($group).'/';
	$types = wrap_category_id($prefix, 'list');
	if (!$types) return [];

	$sql = 'SELECT category_text_id, category_text, text_category_id
		FROM categories_text
		WHERE category_id = %d
		AND text_category_id IN (%s)';
	$sql = sprintf($sql, $category_id, implode(',', array_unique($types)));
	$rows = wrap_db_fetch($sql, 'category_text_id');
	if (!$rows) return [];
	$rows = wrap_translate($rows, 'categories_text', '', true, $language);

	$keys = [];
	foreach ($types as $path => $id)
		$keys[$id] = substr($path, strlen($prefix));
	$texts = [];
	foreach ($rows as $row)
		$texts[$keys[$row['text_category_id']]] = $row['category_text'];
	return $texts;
}

/**
 * categories that have a texts/{group} tree, keyed by category id
 *
 * the group is the second segment of a texts/ identifier, e. g. forms
 * for texts/forms/heading. aliases are included via wrap_category_id()
 *
 * @return array category_id => group
 */
function mf_default_category_text_categories() {
	$types = wrap_category_id('texts/', 'list');
	if (!$types) return [];

	$groups = [];
	foreach (array_keys($types) as $path) {
		$parts = explode('/', $path);
		if (empty($parts[1])) continue;
		$groups[$parts[1]] = $parts[1];
	}
	if (!$groups) return [];

	$categories = [];
	foreach ($groups as $group) {
		$list = wrap_category_id($group.'/', 'list');
		if (!$list) continue;
		foreach ($list as $id)
			$categories[$id] = $group;
	}
	return $categories;
}

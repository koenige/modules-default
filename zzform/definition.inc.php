<?php 

/**
 * default module
 * definition helper functions for forms with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_include_files('zzbrick_tables/_subtable_categories', 'default');

/**
 * get a list of categories to include subtable depending on these categories
 *
 * @param array $values
 *		array $values[$type] or empty
 *		string $values[$type.'_restrict_to'] (optional)
 * @param string $type
 * @param string $category_path (optional, set only if different from $type)
 * @return array
 */
function mf_default_categories_restrict(&$values, $type, $category_path = '') {
	if (!$category_path) $category_path = $type;
	if (isset($values[$type])) return false;
	if (isset($values[$type.'_restrict_to']))
		$restrict_to = 'AND parameters LIKE "%%&'.$values[$type.'_restrict_to'].'=1%%"';
	else
		$restrict_to = '';
	$sql = 'SELECT category_id, category, parameters
			, SUBSTRING_INDEX(path, "/", -1) AS path
		FROM categories
		WHERE main_category_id = %d
		%s
		ORDER BY sequence, path';
	$sql = sprintf($sql
		, wrap_category_id($category_path)
		, $restrict_to
	);
	$values[$type] = wrap_db_fetch($sql, 'category_id');
	foreach ($values[$type] as $category_id => $line) {
		if ($line['parameters'])
			parse_str($line['parameters'], $values[$type][$category_id]['parameters']);
		else
			$values[$type][$category_id]['parameters'] = [];
		if (!empty($values[$type][$category_id]['parameters']['alias']))
			$values[$type][$category_id]['path'] = $values[$type][$category_id]['parameters']['alias'];
		if ($pos = strrpos($values[$type][$category_id]['path'], '/'))
			$values[$type][$category_id]['path'] = substr($values[$type][$category_id]['path'], $pos + 1);
	}
	return true;
}

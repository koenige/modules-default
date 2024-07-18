<?php 

/**
 * default module
 * definition helper functions for forms with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * link a subtable maintable_categories with fields maintable_id, category_id, property
 *
 * @param array $zz existing table definition so far
 * @param string $table name of table, prefix of _categories subtable
 * @param string $path path of main category used for categories
 * @param int $start_no no of field to start with
 */
function mf_default_categories_subtable(&$zz, $table, $path, $start_no) {
	static $definition = [];
	// are there any categories to choose from?
	$tree = wrap_id_tree('categories', $path);
	if (count($tree) === 1) {
		if (count(wrap_category_id($path, 'list')) < 2) return;
	}

	$sql = 'SELECT parameters FROM categories WHERE category_id = %d';
	$sql = sprintf($sql, wrap_category_id($path));
	$parameters = wrap_db_fetch($sql, '', 'single value');
	if ($parameters) parse_str($parameters, $parameters);
	if (empty($parameters['use_subtree'])) {
		$pc[] = [
			'category' => 'Categories',
			'min_records' => 1,
			'min_records_required' => 1,
			'max_records' => 2,
			'category_count' => 1, // no. does not matter here, just that there are categories
			'category_id' => 0
		];
	} else {
		$sql = 'SELECT category_id, category, parameters, path
				, (SELECT COUNT(*) FROM categories sc WHERE sc.main_category_id = categories.category_id) AS category_count
			FROM categories WHERE main_category_id = %d
			ORDER BY sequence, path';
		$sql = sprintf($sql, wrap_category_id($path));
		$pc = wrap_db_fetch($sql, 'category_id', 'numeric');
		$pc = wrap_translate($pc, 'categories', 'category_id');
	}
	foreach ($pc as $index => $category) {
		if (!empty($category['parameters'])) {
			parse_str($category['parameters'], $cparameters);
			$pc[$index] += $cparameters;
		}
		// remove unselectable categories
		if (!$pc[$index]['category_count'] AND empty($pc[$index]['property_of_category']))
			unset($pc[$index]);
	}
	$pc = array_values($pc); // re-write $index
	foreach ($pc as $index => $category) {
		$no = $start_no + $index;
		$zz['fields'][$no] = zzform_include($table.'-categories');
		if (!array_key_exists($table, $definition))
			$definition[$table] = mf_default_categories_subtable_definition($zz['fields'][$no]);
		$def = $definition[$table];

		$zz['fields'][$no]['type'] = 'subtable';
		$zz['fields'][$no]['title'] = $category['category'];
		if (!empty($category['no_append_before']))
			$zz['fields'][$no]['title_tab'] = 'Categories';
		$zz['fields'][$no]['table_name'] = $table.'_categories_'.$category['category_id'];
		$zz['fields'][$no]['unless']['export_mode']['subselect']['prefix'] = (empty($category['no_append_before']) ? '<br>' : '').'<em>'.wrap_text($category['category']).': ';
		$zz['fields'][$no]['unless']['export_mode']['subselect']['suffix'] = '';
		$zz['fields'][$no]['form_display'] = $category['form_display'] ?? 'lines';
		$zz['fields'][$no]['hide_in_list'] = $category['hide_in_list'] ?? false;
		if (isset($category['min_records']))
			$zz['fields'][$no]['min_records'] = intval($category['min_records']);
		elseif ($zz['fields'][$no]['form_display'] === 'set')
			$zz['fields'][$no]['min_records'] = 1;
		if (isset($category['min_records_required']))
			$zz['fields'][$no]['min_records_required'] = intval($category['min_records_required']);
		if (isset($category['max_records']))
			$zz['fields'][$no]['max_records'] = intval($category['max_records']);
		if (!empty($category['explanation']))
			$zz['fields'][$no]['explanation'] = $category['explanation'];
		if (!empty($def['property']) AND !empty($category['property_of_category'])) {
			$zz['fields'][$no]['sql'] .= sprintf(' WHERE /*_PREFIX_*/categories.category_id = %d', $category['category_id']);
			$zz['fields'][$no]['subselect']['sql'] .= sprintf(' WHERE /*_PREFIX_*/categories.category_id = %d', $category['category_id']);
			$zz['fields'][$no]['fields'][$def['category_id']]['hide_in_form'] = true;
			$zz['fields'][$no]['fields'][$def['category_id']]['type'] = 'hidden';
			$zz['fields'][$no]['fields'][$def['category_id']]['value'] = $category['category_id'];
			$zz['fields'][$no]['fields'][$def['category_id']]['def_val_ignore'] = true;
		} elseif (!empty($category['category_id'])) {
			$zz['fields'][$no]['fields'][$def['category_id']]['show_hierarchy_subtree'] = $category['category_id'];
			$zz['fields'][$no]['fields'][$def['category_id']]['sql_ignore'] = 'main_category';
			$main_category_ids = wrap_id_tree('categories', $category['path']);
			$zz['fields'][$no]['sql'] .= sprintf(' WHERE /*_PREFIX_*/categories.main_category_id IN (%s)', implode(',', $main_category_ids));
			$zz['fields'][$no]['subselect']['sql'] .= sprintf(' WHERE /*_PREFIX_*/categories.main_category_id IN (%s)', implode(',', $main_category_ids));
		}
		if (!empty($def['property'])) {
			if (!empty($category['unit']))
				$zz['fields'][$no]['fields'][$def['property']]['unit'] = $category['unit'];
			if (empty($category['property']))
				$zz['fields'][$no]['fields'][$def['property']]['hide_in_form'] = true;
			if (!empty($category['property_size']))
				$zz['fields'][$no]['fields'][$def['property']]['size'] = $category['property_size'];
			if (!empty($category['placeholder']))
				$zz['fields'][$no]['fields'][$def['property']]['placeholder'] = wrap_text($category['placeholder']);
		}
		$zz['fields'][$no]['sql'] .= ' '.$zz['fields'][$no]['sqlorder'];
		$zz['fields'][$no]['fields'][2]['type'] = 'foreign_key';
		if (!empty($def['type_category_id'])) {
			if (!empty($category['own_type_category']) AND empty($category['type_category']))
				$category['type_category_id'] = $category['category_id'];
			$zz['fields'][$no]['fields'][$def['type_category_id']]['value'] = $category['type_category_id'] ?? wrap_category_id($category['type_category'] ?? $path);
			$zz['fields'][$no]['fields'][$def['type_category_id']]['for_action_ignore'] = true;
		}
		$zz['fields'][$no]['if'][1]['list_suffix'] = '</del>';
		if (!empty($zz['fields'][$no]['fields'][$def['sequence']])) {
			$zz['fields'][$no]['fields'][$def['sequence']]['type'] = 'sequence';
			$zz['fields'][$no]['fields'][$def['sequence']]['auto_value'] = 'increment';
		}
		if (!empty($category['default']))
			$zz['fields'][$no]['if']['insert']['fields'][$def['category_id']]['default'] = wrap_category_id($category['default']);
	}

	// do not set list_append_next for last visible element
	$pc = array_reverse($pc);
	$last_visible_found = false;
	$last_no = $no;

	foreach ($pc as $index => $category) {
		$no = $last_no - $index;
		if ($zz['fields'][$no]['hide_in_list']) {
			if (empty($parameters['no_separator']))
				$zz['fields'][$no]['separator'] = true;
		} elseif (!$last_visible_found) {
			$last_visible_found = true;
		} else {
			$zz['fields'][$no]['unless']['export_mode']['list_append_next'] = true;
			if (empty($parameters['no_separator']))
				$zz['fields'][$no]['separator'] = true;
		}
	}
}

/**
 * get field nos. of subtable
 *
 * @param array $zz
 * @return array key = field_name, value = no.
 */
function mf_default_categories_subtable_definition($zz) {
	$def = [];
	foreach ($zz['fields'] as $no => $field) {
		if (empty($field['field_name'])) continue;
		$def[$field['field_name']] = $no;
	}
	return $def;
}

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
	$last_category_id = array_keys($values[$type]);
	$last_category_id = end($last_category_id);
	foreach ($values[$type] as $category_id => $line) {
		if ($line['parameters'])
			parse_str($line['parameters'], $values[$type][$category_id]['parameters']);
		else
			$values[$type][$category_id]['parameters'] = [];
		if (!empty($values[$type][$category_id]['parameters']['alias']))
			$values[$type][$category_id]['path'] = $values[$type][$category_id]['parameters']['alias'];
		if ($pos = strrpos($values[$type][$category_id]['path'], '/'))
			$values[$type][$category_id]['path'] = substr($values[$type][$category_id]['path'], $pos + 1);
		if ($category_id === $last_category_id)
			$values[$type][$category_id]['last_category'] = true;
	}
	return true;
}

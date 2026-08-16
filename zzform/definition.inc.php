<?php 

/**
 * default module
 * definition helper functions for forms with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_include('context', 'default');


/**
 * link a subtable maintable_categories with fields maintable_id, category_id, property
 *
 * @param array $zz existing table definition so far
 * @param string $table name of table, prefix of _categories subtable
 * @param string $path path of main category used for categories
 * @param int $start_no no of field to start with
 * @param string $restrict_to (optional)
 */
function mf_default_categories_subtable(&$zz, $table, $path, $start_no, $restrict_to = '') {
	static $definition = [];
	// are there any categories to choose from?
	$tree = wrap_id_tree('categories', $path);
	if (count($tree) === 1) {
		if (count(wrap_category_id($path, 'list')) < 2) return;
	}

	$sql = 'SELECT parameters FROM categories WHERE category_id = /*_ID categories %s _*/';
	$sql = sprintf($sql, $path);
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
			FROM categories WHERE main_category_id = /*_ID categories %s _*/
			ORDER BY sequence, path';
		$sql = sprintf($sql, $path);
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
		if ($restrict_to AND !mf_default_category_context($pc[$index], $restrict_to))
			unset($pc[$index]);
	}
	if (!$pc) return;
	$pc = array_values($pc); // re-write $index
	foreach ($pc as $index => $category) {
		$no = $start_no + $index;
		$zz['fields'][$no] = zzform_include($table.'-categories');
		if (!array_key_exists($table, $definition))
			$definition[$table] = mf_default_categories_subtable_definition($zz['fields'][$no]);
		$def = $definition[$table];
		$form_def = $category['zzform_def'] ?? [];

		$zz['fields'][$no]['type'] = 'subtable';
		$zz['fields'][$no]['title'] = $category['category'];
		if (array_key_exists('class', $category))
			$zz['fields'][$no]['class'] = $category['class'];
		if (!empty($category['no_append_before']))
			$zz['fields'][$no]['title_tab'] = 'Categories';
		$zz['fields'][$no]['table_name'] = $table.'_categories_'.$category['category_id'];
		if (empty($category['no_append']))
			$zz['fields'][$no]['unless']['export_mode']['subselect']['prefix'] = '<p><em>'.wrap_text($category['category']).'</em>: ';
		$zz['fields'][$no]['unless']['export_mode']['subselect']['suffix'] = empty($category['no_append']) ? '</p>' : '';
		$zz['fields'][$no]['form_display'] = $form_def['form_display'] ?? 'lines';
		if ($zz['fields'][$no]['form_display'] === 'key_value') {
			if (empty($def['property'])) wrap_error(['You need a property field if using `key_value` subtables.']);
			$zz['fields'][$no]['fields'][$def['category_id']]['subtable_key'] = true;
			$zz['fields'][$no]['fields'][$def['property']]['subtable_value'] = true;
			$zz['fields'][$no]['fields'][$def['property']]['size'] = 32;
		}
		$zz['fields'][$no]['hide_in_list'] = $form_def['hide_in_list'] ?? false;
		if (isset($form_def['min_records']))
			$zz['fields'][$no]['min_records'] = intval($form_def['min_records']);
		if (isset($form_def['min_records_required']))
			$zz['fields'][$no]['min_records_required'] = intval($form_def['min_records_required']);
		if (isset($form_def['max_records']))
			$zz['fields'][$no]['max_records'] = intval($form_def['max_records']);
		if (!empty($form_def['explanation']))
			$zz['fields'][$no]['explanation'] = $form_def['explanation'];
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
			if (!empty($zz['fields'][$no]['subselect']['sql']))
				$zz['fields'][$no]['subselect']['sql'] .= sprintf(' WHERE /*_PREFIX_*/categories.main_category_id IN (%s)', implode(',', $main_category_ids));
		} else {
			$zz['fields'][$no]['fields'][$def['category_id']]['show_hierarchy_subtree'] = wrap_category_id($path);
		}
		if (!empty($def['property'])) {
			if (!empty($category['unit']))
				$zz['fields'][$no]['fields'][$def['property']]['unit'] = $category['unit'];
			if (empty($category['property']))
				$zz['fields'][$no]['fields'][$def['property']]['hide_in_form'] = true;
			if (!empty($category['property_size']))
				$zz['fields'][$no]['fields'][$def['property']]['size'] = $category['property_size'];
			if (!empty($form_def['placeholder']))
				$zz['fields'][$no]['fields'][$def['property']]['placeholder'] = $form_def['placeholder'];
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
		if (array_key_exists('sequence', $def) AND !empty($zz['fields'][$no]['fields'][$def['sequence']])) {
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
		if (empty($parameters['no_separator']))
			$zz['fields'][$no]['separator'] = true;
		if ($zz['fields'][$no]['hide_in_list']) continue;
		if (!$last_visible_found)
			$last_visible_found = true;
		elseif (empty($category['no_append']))
			$zz['fields'][$no]['unless']['export_mode']['list_append_next'] = true;
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
 * add '_reverse' for reverse relations if both foreign keys are the same
 * e. g. contact_id, main_contact_id
 *
 * @param array $values
 *		array $values[$type] or empty
 *		string|array $values['context'][$type] (optional)
 * @param string $type
 * @param string $category_path (optional, set only if different from $type)
 * @return array
 */
function mf_default_categories_restrict(&$values, $type, $category_path = NULL) {
	if (isset($values[$type])) return false; // do not overwrite existing data

	$contexts = mf_default_contexts($values, $type);

	$sql = 'SELECT category_id, category, parameters
			, SUBSTRING_INDEX(path, "/", -1) AS path
		FROM categories
		WHERE main_category_id = /*_ID categories %s _*/
		ORDER BY sequence, path';
	$sql = sprintf($sql, $category_path ?? $type);
	$lines = wrap_db_fetch($sql, 'category_id', 'numeric');
	$last_category_id = $lines ? end($lines)['category_id'] : null;
	$new = [];
	$filtered = [];
	foreach ($lines as $index => $line) {
		if ($line['parameters'])
			parse_str($line['parameters'], $line['parameters']);
		else
			$line['parameters'] = [];

		if ($contexts) {
			if (!mf_default_category_context($line['parameters'], $contexts))
				continue;
			$line['parameters'] = mf_default_apply_if($line['parameters'], $contexts);
			$line = mf_default_apply_reversed($line, $contexts);
		}

		if (!empty($line['parameters']['alias']))
			$line['path'] = $line['parameters']['alias'];
		if ($pos = strrpos($line['path'], '/'))
			$line['path'] = substr($line['path'], $pos + 1);
		if ($last_category_id !== null AND $line['category_id'].'' === $last_category_id.'')
			$line['last_category'] = true;
		if (!empty($line['parameters']['split_title']) AND strstr($line['category'], ' / ')) {
			$title = explode(' / ', $line['category']);
			$line['category'] = !empty($line['reverse']) ? $title[1] : $title[0];
		}
		if (!empty($line['parameters']['association'])) {
			$new[$index] = $line;
			$new[$index]['parameters']['zzform_def']['integrate_in_next'] = true;
			$new[$index]['association'] = true;
		}
		$filtered[] = $line;
	}
	foreach ($new as $pos => $association)
		array_splice($filtered, $pos - 2, 0, [$association]);
	$values[$type] = $filtered;
	return true;
}

/**
 * for appended categories, add title_tab
 *
 * @param array $fields = $zz['fields'], will change
 * @param int $no_start
 * @param int $no_end
 * @return bool
 */
function mf_default_categories_details_tab(&$fields, $no_start, $no_end) {
	static $marked = false;
	if ($marked) return;

	for ($no = $no_start; $no <= $no_end; $no++) {
		if (empty($fields[$no])) continue;
		if (!empty($fields[$no]['hide_in_list'])) continue;
		$fields[$no]['title_tab'] = wrap_text('Details');
		$marked = true;
	}
	return $marked;
}

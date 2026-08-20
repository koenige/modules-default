<?php

/**
 * default module
 * form helpers
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * read or test form_show[table.field] from parsed parameters
 *
 * @param array $parameters parsed parameters
 * @param string $table table name (e.g. participations)
 * @param string $field_name column name (e.g. status_category_id)
 * @param int|null $show 0 or 1 to test; omit to get stored value
 * @return bool|int|null with $show: bool; without: 0, 1, or null if unset
 */
function mf_default_form_show(array $parameters, $table, $field_name, $show = null) {
	$key = $table.'.'.$field_name;
	if (!isset($parameters['form_show'][$key])) {
		return $show === null ? null : false;
	}
	$value = (int) $parameters['form_show'][$key];
	if ($show === null) {
		return $value;
	}
	return $value === (int) $show;
}

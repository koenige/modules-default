<?php

/**
 * default module
 * search
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_search() {
	if (!empty($_GET['q'])) {
		$q = wrap_db_escape($_GET['q']);
		if (strstr($q, ' ')) {
			$q = explode(' ', $q);
		} else {
			$q = [$q];
		}
		$files = wrap_include_files('search');
		if (!$files) return false;
		$data['search_results'] = false;
		foreach (array_keys($files) as $module) {
			$function = sprintf('mf_%s_search', $module);
			$results = $function($q);
			if ($results[$module]) $data['search_results'] = true;
			$data['modules'][$module]['results'] = wrap_template(sprintf('search-%s', $module), $results);
		}
		if ($search_order = wrap_get_setting('search_module_order')) {
			$unsorted = $data['modules'];
			$data['modules'] = [];
			foreach ($search_order as $module) {
				if (!array_key_exists($module, $unsorted)) continue;
				$data['modules'][$module] = $unsorted[$module];
			}
		}
		$data['modules'] = array_values($data['modules']);
		$page['meta'][] = [
			'name' => 'robots',
			'content' => 'noindex, follow, noarchive'
		];
		if (!$data['search_results'] AND wrap_get_setting('default_404_no_search_results'))
			$page['status'] = 404;
	}

	$page['query_strings'] = ['q'];
	$data['q'] = !empty($_GET['q']) ? $_GET['q'] : '';
	$page['text'] = wrap_template('search', $data);
	return $page;
}

/**
 * get conditions for values from translated fields
 *
 * @param array $q
 * @param array $search
 * @return string
 */
function mod_default_search_translations($q, $fields) {
	$where = [];
	$translations = [];
	foreach ($fields as $table => $definition) {
		$sql = 'SELECT translationfield_id, table_name, field_name, field_type
				, "%s" AS id_field_name
			FROM _translationfields
			WHERE db_name = DATABASE()
			AND table_name = "%s"
			AND field_name IN ("%s")';
		$sql = sprintf($sql
			, $definition['id_field_name']
			, $table
			, implode('", "', $definition['fields'])
		);
		$translations = array_merge_recursive($translations
			, wrap_db_fetch($sql, ['field_type', 'translationfield_id'])
		);
	}
	$sql = 'SELECT translationfield_id, field_id
		FROM _translations_%s
		WHERE translation LIKE "%%%s%%"
		AND translationfield_id IN (%s)';
	foreach ($translations as $translation_table => $fields) {
		$tkeys = [];
		foreach ($fields as $field) {
			$tkeys[$field['translationfield_id']] = $field;
		}
		foreach ($q as $string) {
			$this_sql = sprintf($sql, $translation_table, $string, implode(',', array_keys($tkeys)));
			$result = wrap_db_fetch($this_sql, ['translationfield_id', 'field_id']);
			foreach ($result as $t_field_id => $field_id) {
				$where[] = sprintf('%s.%s IN (%s)'
					, $tkeys[$t_field_id]['table_name']
					, $tkeys[$t_field_id]['id_field_name']
					, implode(',', array_keys($field_id))
				);
			}
		}
	}
	$where = implode(' OR ', $where);
	return $where;
}

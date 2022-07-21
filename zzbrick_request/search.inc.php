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
	}
	
	$files = wrap_include_files('search');
	$data = [];
	foreach (array_keys($files) as $module) {
		$function = sprintf('mf_%s_search', $module);
		$data += $function($q);
	}

	$page['query_strings'] = ['q'];
	$data['q'] = !empty($_GET['q']) ? $_GET['q'] : '';
	$page['text'] = wrap_template('search', $data);
	return $page;
}

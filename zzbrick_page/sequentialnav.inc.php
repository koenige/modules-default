<?php

/**
 * default module
 * show links for sequential navigation between pages (prev, next, up)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show links for sequential navigation between pages
 *
 * @param array $params -
 * @return array $page
 */
function page_sequentialnav(&$params = []) {
	global $zz_page;

	$data = [];
	$link_relations = ['prev', 'next', 'up'];
	foreach ($link_relations as $rel) {
		if (empty($zz_page[$rel])) continue;
		$data[] = [
			'rel' => $rel,
			'rel_title' => $zz_page[$rel]['rel_title'] ?? wrap_text(ucfirst($rel)),
			'href' => $zz_page[$rel]['url'],
			'title' => $zz_page[$rel]['title']
		];
	}
	if (!$data) return '';
	return wrap_template('sequentialnav', $data);
}

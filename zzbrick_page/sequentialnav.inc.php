<?php

/**
 * default module
 * show links for sequential navigation between pages (prev, next, up)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show links for sequential navigation between pages
 *
 * @param array $params -
 * @return array $page
 */
function page_sequentialnav(&$params = []) {
	$data = [];
	$link_relations = ['prev', 'next', 'up'];
	foreach ($link_relations as $rel) {
		$meta = wrap_page_meta($rel);
		if (empty($meta)) continue;
		$data[] = [
			'rel' => $rel,
			'rel_title' => $meta['rel_title'] ?? wrap_text(ucfirst($rel)),
			'href' => $meta['url'],
			'title' => $meta['title']
		];
	}
	if (!$data) return '';
	return wrap_template('sequentialnav', $data);
}

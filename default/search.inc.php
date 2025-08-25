<?php

/**
 * default module
 * search functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mf_default_search($q) {
	$where_sql = '(title LIKE "%%%s%%"
		OR content LIKE "%%%s%%"
		OR description LIKE "%%%s%%")';
	$where = [];
	foreach ($q as $string) {
		$where[] = sprintf($where_sql, $string, $string, $string);
	}
	
	$t_where = mod_default_search_translations($q, [
		'webpages' => [
			'id_field_name' => 'page_id',
			'fields' => ['title', 'content', 'description']
		]
	]);

	$sql = 'SELECT page_id
			, CONCAT(identifier, IF(STRCMP(ending, "none"), ending, "")) AS identifier
			, title, description
		FROM webpages
		WHERE (%s
		%s)
		AND live = "yes"
		AND identifier NOT LIKE "%%*%%"
		AND (ISNULL(parameters) OR parameters NOT LIKE "%%&search=0%%")
		AND website_id = %d
		ORDER BY identifier';
	$sql = sprintf($sql
		, implode(' AND ', $where)
		, $t_where ? ' OR '.$t_where : ''
		, wrap_setting('website_id') ?? 1
	);
	$data['default'][0]['webpages'] = wrap_db_fetch($sql, 'page_id');
	if (!$data['default'][0]['webpages'])
		return ['default' => []];
	foreach ($data['default'][0]['webpages'] as $page_id => $page) {
		foreach (wrap_setting('auth_urls') as $url) {
			if (!str_starts_with($page['identifier'], $url)) continue;
			unset($data['default'][0]['webpages'][$page_id]);
		}
	}
	$data['default'][0]['webpages'] = wrap_translate($data['default'][0]['webpages'], 'webpages');
	$data['default'][0]['webpages'] = mf_default_webpages_media($data['default'][0]['webpages']);
	return $data;
}

function mf_default_webpages_media($data) {
	static $opengraph_img = [];
	if (!$data) return [];
	$media = function_exists('wrap_get_media') ? wrap_get_media(array_keys($data), 'webpages', 'page') : [];
	foreach ($media as $id => $files)
		$data[$id] += $files;
	foreach ($data as $id => $line) {
		if (!empty($line['images'])) continue;
		if (!wrap_setting('active_theme')) continue;
		if (!$opengraph_img) {
			$filename = sprintf('%s/%s/opengraph.png', wrap_setting('themes_dir'), wrap_setting('active_theme'));
			if (!file_exists($filename)) break;
			$opengraph_img = [
				'filename' => 'opengraph',
				'thumb_extension' => 'png',
				'no_image_size' => 1,
				'no_'
			];
		}
		$data[$id]['images'][] = $opengraph_img;
	}
	return $data;
}

<?php

/**
 * default module
 * update tables
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * update tables, e. g. to change identifiers, create thumbnails etc.
 *
 * @param array $params
 *		[0]: string name of table to update
 * @return array $page
 */
function mod_default_make_update($params) {
	// @todo limit 0,50 or so
	// @todo use jobqueue
	
	if (count($params) !== 1) return false;
	$script = str_replace('_', '-', $params[0]);

	// get primary key
	$sql = 'SHOW KEYS FROM %s WHERE Key_name = "PRIMARY"';
	$sql = sprintf($sql, wrap_db_escape($params[0]));
	$pkey = wrap_db_fetch($sql);
	if (!$pkey)
		wrap_quit(404, sprintf('Table %s not found', $params[0]));

	$page['query_strings'][] = 'thumbs';
	$page['query_strings'][] = 'field';
	$page['query_strings'][] = 'limit';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$data['no_post'] = true;
		$page['text'] = wrap_template('update', $data);
		return $page;
	}

	// thumbnail creation in background?
	if (!empty($_GET['thumbs']) AND !empty($_GET['field'])) {
		wrap_include('zzform.php', 'zzform');
		$zz = zzform_include($script);
		$ops = zzform($zz);
		$data = json_decode($ops['output'], true);
		$data['thumb_field'] = implode('-', $ops['thumb_field']);
		$data['table'] = $params[0];
		if ($ops['result'] === 'thumbnail created') {
			$data['thumbnail_created'] = true;
		} else {
			$data['thumbnail_failed'] = true;
			$data['record_id'] = $_GET['thumbs'];
			$data['error'] = implode('<br>', $data['error']);
			wrap_error(sprintf(
				'Creation of thumbnail for medium ID %s failed. (Reason: %s)'
				, $_GET['thumbs'], json_encode($ops['error'])
			));
			$page['status'] = $ops['page']['status'] ?? 503;
		}
		$page['text'] = wrap_template('update', $data);
		return $page;
	}

	$limit = $_GET['limit'] ?? 0;

	$sql = 'SELECT %s
		FROM %s
		ORDER BY %s
		LIMIT %d, /*_SETTING default_update_limit_per_run _*/';
	$sql = sprintf($sql
		, $pkey['Column_name']
		, wrap_db_escape($params[0])
		, $pkey['Column_name']
		, $limit
	);
	$data = wrap_db_fetch($sql, $pkey['Column_name']);
	if (!$data) {
		$page['text'] = sprintf('<p>No records found in table <code>%s</code>.</p>', $params[0]);
		return $page;
	}

	foreach ($data as $line)
		zzform_update($script, [$pkey['Column_name'] => $line[$pkey['Column_name']]]);

	// call next page per background job
	$url_path = parse_url(wrap_setting('request_uri'), PHP_URL_PATH);
	$url_query = parse_url(wrap_setting('request_uri'), PHP_URL_QUERY);
	if ($url_query) parse_str($url_query, $url_query);
	else $url_query = [];
	$url_query['limit'] = $limit + wrap_setting('default_update_limit_per_run');
	$url = sprintf('%s?%s', $url_path, http_build_query($url_query));
	wrap_job($url, ['trigger' => true]);

	$page['text'] = sprintf('<p>Updated %s table.</p>', $params[0]);
	return $page;
}

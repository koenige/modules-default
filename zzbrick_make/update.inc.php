<?php

/**
 * default module
 * update tables
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
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

	// thumbnail creation in background?
	if (!empty($_GET['thumbs']) AND !empty($_GET['field'])) {
		wrap_include_files('zzform.php', 'zzform');
		$zz = zzform_include($script);
		$ops = zzform($zz);
		$page['query_strings'][] = 'thumbs';
		$page['query_strings'][] = 'field';
		if ($ops['result'] === 'thumbnail created') {
			$page['text'] = sprintf('<p>Thumbnail %s for %s ID %s created.</p>'
				, $_GET['field'], $params[0], $_GET['thumbs']
			);
		} else {
			$page['text'] = sprintf('<p>Creation of thumbnail %s for %s ID %s failed.</p>'
				, $_GET['field'], $params[0], $_GET['thumbs']
			);
			wrap_error(sprintf(
				'Creation of thumbnail for medium ID %s failed. (Reason: %s)'
				, $_GET['thumbs'], json_encode($ops['error'])
			));
			$page['status'] = 503;
		}
		return $page;
	}

	$sql = 'SELECT %s
		FROM %s
		ORDER BY %s';
	$sql = sprintf($sql
		, $pkey['Column_name']
		, wrap_db_escape($params[0])
		, $pkey['Column_name']
	);
	$data = wrap_db_fetch($sql, $pkey['Column_name']);
	if (!$data) {
		$page['text'] = sprintf('<p>No records found in table <code>%s</code>.</p>', $params[0]);
		return $page;
	}

	foreach ($data as $line) {
		$values = [];
		$values['action'] = 'update';
		$values['POST'][$pkey['Column_name']] = $line[$pkey['Column_name']];
		$ops = zzform_multi($script, $values);
	}
	$page['text'] = sprintf('<p>Updated %s table.</p>', $params[0]);
	return $page;
}

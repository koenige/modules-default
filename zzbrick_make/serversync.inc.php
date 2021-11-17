<?php

/**
 * default module
 * Synchronisation of data from development and production server
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2018, 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Synchronisation of data from development and production server
 *
 * @param array $params -
 * @return array $page
 */
function mod_default_make_serversync($params) {
	global $zz_conf;
	require __DIR__.'/../zzbrick_request/maintenance.inc.php';
	
	if (!empty($_POST['return_last_logging_entry'])) {
		$data = mod_default_maintenance_last_log();
		$page['text'] = json_encode($data, true);
		$page['content_type'] = 'json';
		$page['headers']['filename'] = 'logging_last.json';
		return $page;
	} elseif (!empty($_POST['add_log'])) {
		$out = mod_default_maintenance_add_logging($_POST['add_log']);
		$page['text'] = json_encode($out, true);
		$page['content_type'] = 'json';
		$page['headers']['filename'] = 'logging_add.json';
		return $page;
	} elseif (!empty($_GET['get_log_from_id'])) {
		list($log, $limit) = mod_default_maintenance_read_logging($_GET['get_log_from_id']);
		$page['text'] = json_encode($log, true);
		$page['query_strings'] = ['get_log_from_id'];
		$page['content_type'] = 'json';
		if ($limit) {
			$page['headers']['filename'] = sprintf('logging_%d-%d.json', $_GET['get_log_from_id'], $_GET['get_log_from_id'] + $limit - 1);
		} else {
			$page['headers']['filename'] = sprintf('logging_%d.json', $_GET['get_log_from_id']);
		}
		return $page;
	}
	wrap_quit(403, wrap_text('This URL is for synchronising a production and a development server only. No direct access is possible.'));
}

<?php

/**
 * default module
 * write config/allowed-hostnames.json from known hostnames
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 *
 * Variables
 * translate_pot = admin
 */


/**
 * Show known hostnames and write config/allowed-hostnames.json
 *
 * @param array $params
 * @return array $page
 */
function mod_default_make_allowed_hostnames($params) {
	wrap_access_quit('default_maintenance');

	$data = [];
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$hostnames = mod_default_make_allowed_hostnames_collect();
		if (mod_default_make_allowed_hostnames_write($hostnames))
			wrap_redirect_change();
		$data = mod_default_make_allowed_hostnames_data($hostnames);
		$data['write_error'] = true;
	} else {
		$data = mod_default_make_allowed_hostnames_data();
	}

	$page = [];
	$page['extra']['css'][] = 'default/maintenance.css';
	$page['text'] = wrap_template('allowed-hostnames', $data);
	$page['title'] = wrap_text('Allowed hostnames');
	$page['breadcrumbs'][]['title'] = wrap_text('Allowed hostnames');
	return $page;
}

/**
 * Template data: current file vs proposed list
 *
 * @param array|null $proposed
 * @return array
 */
function mod_default_make_allowed_hostnames_data($proposed = null) {
	if ($proposed === null)
		$proposed = mod_default_make_allowed_hostnames_collect();

	$file = wrap_setting('config_dir').'/allowed-hostnames.json';
	$current = mod_default_make_allowed_hostnames_read($file);

	$current_rows = [];
	if (is_array($current)) {
		foreach ($current as $hostname)
			$current_rows[] = ['hostname' => $hostname];
	}
	$proposed_rows = [];
	foreach ($proposed as $hostname)
		$proposed_rows[] = ['hostname' => $hostname];

	return [
		'file' => $file,
		'exists' => is_array($current),
		'current' => $current_rows,
		'proposed' => $proposed_rows,
		'has_proposed' => (bool) $proposed,
		'unchanged' => is_array($current) AND $current === $proposed,
	];
}

/**
 * Known hostnames for this installation (settings, current host, websites.json)
 *
 * @return array
 */
function mod_default_make_allowed_hostnames_collect() {
	$hostnames = wrap_error_hostnames();
	$hostnames[] = wrap_setting('hostname');
	$hostnames[] = wrap_setting('site');

	if (wrap_setting('multiple_websites')) {
		$parsed = wrap_websites_parse();
		foreach (array_keys($parsed['by_hostname']) as $hostname)
			$hostnames[] = $hostname;
	}

	$normalized = [];
	foreach ($hostnames as $hostname) {
		if (!is_string($hostname)) continue;
		$hostname = wrap_http_hostname_normalize(rtrim(trim($hostname), '.'));
		if ($hostname === '') continue;
		$normalized[$hostname] = true;
	}
	$list = array_keys($normalized);
	sort($list);
	return $list;
}

/**
 * Hostnames already stored in allowed-hostnames.json
 *
 * @param string $file
 * @return array|null null if the file is missing
 */
function mod_default_make_allowed_hostnames_read($file) {
	if (!file_exists($file)) return null;

	$decoded = json_decode(file_get_contents($file), true);
	if (!is_array($decoded)) return [];

	$list = [];
	foreach ($decoded as $hostname) {
		if (!is_string($hostname)) continue;
		$hostname = trim($hostname);
		if ($hostname === '') continue;
		$list[] = $hostname;
	}
	return $list;
}

/**
 * Write allowed-hostnames.json
 *
 * @param array $hostnames
 * @return bool
 */
function mod_default_make_allowed_hostnames_write($hostnames) {
	$dir = wrap_setting('config_dir');
	if (wrap_mkdir($dir) === false) return false;

	$json = json_encode(
		$hostnames,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ($json === false) return false;

	return file_put_contents($dir.'/allowed-hostnames.json', $json."\n") !== false;
}

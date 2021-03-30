<?php

/**
 * default module
 * show data from database as .cfg file
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_cfgfile($params) {
	// does query exist?
	wrap_sql('cfg', 'set');
	$sql = wrap_sql('data_'.$params[0]);
	if (!$sql) return false;

	// get raw data
	$raw = wrap_db_fetch($sql, 'identifier');
	$raw = array_values($raw);

	$data = [];
	foreach ($raw as $index => $entry) {
		$data[$index]['identifier'] = $entry['identifier'];
		if (!empty($entry['parameters'])) {
			parse_str($entry['parameters'], $parameters);
			$entry += $parameters;
		}
		foreach ($entry as $key => $value) {
			if (in_array($key, ['identifier', 'parameters'])) continue;
			if (!$value AND $value !== 0 AND $value !== '0') continue;
			if (wrap_substr($key, '_list', 'end')) {
				$key = substr($key, 0, - strlen('_list'));
				$value = explode(',', $value);
				foreach ($value as $subkey => $subval) {
					$data[$index]['keys'][] = [
						'key' => $key.'['.$subkey.']', 'value' => $subval
					];
				}
			} elseif (is_array($value)) {
				foreach ($value as $subkey => $subval) {
					$data[$index]['keys'][] = [
						'key' => $key.'['.$subkey.']', 'value' => $subval
					];
				}
			} else {
				$data[$index]['keys'][] = [
					'key' => $key, 'value' => $value
				];
			}
		}
	}
	
	$page['text'] = wrap_template('cfgfile', $data);
	$page['text'] = str_replace('\%', '%', $page['text']);
	$page['text'] = explode("\n", $page['text']);
	foreach ($page['text'] as $index => $line) {
		$page['text'][$index] = rtrim($line);
	}
	$page['text'] = implode("\n", $page['text']);
	$page['content_type'] = 'txt';
	return $page;
}

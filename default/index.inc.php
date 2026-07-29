<?php 

/**
 * default module
 * shared helpers for generated index.json files
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Encode index as pretty JSON
 *
 * @param array $entries
 * @return string
 */
function mf_default_index_json_encode(array $entries) {
	return json_encode(
		$entries,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	)."\n";
}

/**
 * Is an index.json up to date for one package?
 *
 * @param string $package
 * @param string $index_path path under package folder, e.g. help/index.json
 * @param callable $collect collect function($package): array
 * @return string empty|missing|current|outdated
 */
function mf_default_index_status($package, $index_path, callable $collect) {
	$folder = wrap_package_folder($package);
	if (!$folder) return 'empty';

	$filename = $folder.'/'.$index_path;
	$old = file_exists($filename) ? file_get_contents($filename) : '';
	$entries = $collect($package);
	$new = mf_default_index_json_encode($entries);

	if (!count($entries) AND $old === '') return 'empty';
	if (!count($entries)) return 'outdated';
	if ($old === '') return 'missing';
	if ($old === $new) return 'current';
	return 'outdated';
}

/**
 * JSON diff stats between existing index and scan
 *
 * @param string $old_content
 * @param array $new_entries
 * @return array added, deleted, updated (int)
 */
function mf_default_index_diff_stats($old_content, array $new_entries) {
	$stats = ['added' => 0, 'deleted' => 0, 'updated' => 0];
	$old = json_decode($old_content, true);
	if (!is_array($old)) $old = [];

	foreach ($new_entries as $key => $entry) {
		if (!array_key_exists($key, $old))
			$stats['added']++;
		elseif ($old[$key] !== $entry)
			$stats['updated']++;
	}
	foreach ($old as $key => $entry) {
		if (!array_key_exists($key, $new_entries))
			$stats['deleted']++;
	}
	return $stats;
}

/**
 * Write scanned index
 *
 * @param string $package
 * @param string $filename
 * @param callable $collect collect function($package): array
 * @return string
 */
function mf_default_index_write($package, $filename, callable $collect) {
	$entries = $collect($package);
	$new = mf_default_index_json_encode($entries);
	$old = file_exists($filename) ? file_get_contents($filename) : '';

	if ($old === $new) return 'file_content_unchanged';
	if (file_put_contents($filename, $new) === false) return 'file_not_writable';
	return 'file_written';
}

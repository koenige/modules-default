<?php 

/**
 * default module
 * check for deprecated code patterns
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * check for deprecated code patterns in the codebase
 * based on deprecations.tsv per module
 *
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_deprecations($params) {
	// look for deprecations.tsv
	$data = mod_default_make_deprecations_readfiles();
	if (!$data) $data = [];
	ksort($data);
	$data = array_values($data);

	// check which deprecations already have been resolved
	foreach ($data as $index => $line) {
		$data[$index]['index'] = $index;
		$data[$index]['resolved'] = mod_default_make_deprecations_check($line);
		if ($data[$index]['resolved']) {
			unset($data[$index]);
			continue;
		}
		// scan for deprecated patterns
		$data[$index]['found'] = mod_default_make_deprecations_scan($line);
		if (!$data[$index]['found']) {
			// pattern not found in code, mark as resolved automatically
			mod_default_make_deprecations_log($line, 'not_found');
			unset($data[$index]);
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		foreach ($_POST as $key => $value) {
			if (!str_starts_with($key, 'resolved_')) continue;
			$index = substr($key, 9); // remove 'resolved_' prefix
			if (!array_key_exists($index, $data)) continue;
			mod_default_make_deprecations_resolve($data[$index]);
		}
		wrap_redirect_change();
	}
	if (!$data) $data['not_found'] = true;

	$page['text'] = wrap_template('deprecations', $data);
	$page['title'] = wrap_text('Deprecated Code Patterns');
	$page['breadcrumbs'][]['title'] = wrap_text('Deprecated Code Patterns');
	return $page;
}

/**
 * read a deprecations.tsv file and interpret it
 *
 * @return array
 */
function mod_default_make_deprecations_readfiles() {
	$lines = wrap_tsv_parse('deprecations', 'modules/custom');
	if (!$lines) return [];
	
	$data = [];
	foreach ($lines as $identifier => $line) {
		$package = $line['_package'];
		$key = $identifier.'-'.$package;
		$data[$key] = [
			'identifier' => $identifier,
			'search_text' => $line['Search Text'],
			'message' => $line['Message'],
			'exclude_pattern' => $line['Exclude Pattern'] ?? '',
			'package' => $package,
			'key' => $key
		];
	}
	return $data;
}

/**
 * check if deprecation already has been resolved
 *
 * @param array $line
 * @return bool
 */
function mod_default_make_deprecations_check($line) {
	$logfile = wrap_setting('log_dir').'/deprecations.log';
	if (!file_exists($logfile)) return false;
	
	$logs = file($logfile);
	foreach ($logs as $log) {
		$log = explode(' ', trim($log));
		if ($log[0] !== $line['key']) continue;
		return true;
	}
	return false;
}

/**
 * scan codebase for deprecated pattern
 *
 * @param array $line
 * @return array list of files where pattern was found
 */
function mod_default_make_deprecations_scan($line) {
	$cms_dir = wrap_setting('cms_dir');
	$search_text = $line['search_text'];
	$exclude_pattern = $line['exclude_pattern'] ?? '';
	$found = [];
	
	// use grep to search for the pattern
	$cmd = sprintf(
		'grep -r -l --include="*.php" --include="*.inc.php" %s %s 2>/dev/null',
		escapeshellarg($search_text),
		escapeshellarg($cms_dir)
	);
	
	exec($cmd, $output, $return_var);
	
	if ($return_var === 0 && !empty($output)) {
		// split exclusion patterns by semicolon
		$exclude_patterns = [];
		if ($exclude_pattern) {
			$exclude_patterns = array_map('trim', explode(';', $exclude_pattern));
		}
		
		foreach ($output as $file) {
			// make path relative to cms_dir for display
			$relative_path = str_replace($cms_dir.'/', '', $file);
			
			// skip files matching any exclusion pattern
			$excluded = false;
			foreach ($exclude_patterns as $pattern) {
				if ($pattern && strpos($relative_path, $pattern) !== false) {
					$excluded = true;
					break;
				}
			}
			if ($excluded) continue;
			
			$found[]['file'] = $relative_path;
		}
	}
	
	return $found;
}

/**
 * mark deprecation as resolved
 *
 * @param array $line
 * @return void
 */
function mod_default_make_deprecations_resolve($line) {
	mod_default_make_deprecations_log($line, 'resolved');
}

/**
 * read or write deprecation log
 *
 * @param array $line
 * @param string $mode ('resolved', 'not_found')
 * @return bool
 */
function mod_default_make_deprecations_log($line, $mode) {
	$logfile = wrap_setting('log_dir').'/deprecations.log';
	if (!file_exists($logfile)) touch($logfile);
	
	error_log(sprintf("%s %s %s %s\n", $line['key'], date('Y-m-d H:i:s'), $mode, $_SESSION['username'] ?? 'system'), 3, $logfile);
	return true;
}


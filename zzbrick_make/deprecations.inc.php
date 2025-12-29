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
		// escape %%% for template output
		$data[$index]['search_text'] = str_replace('%%%', '%%% explain', $data[$index]['search_text']);
		$data[$index]['message'] = str_replace('%%%', '%%% explain', $data[$index]['message']);
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
			'type' => $line['Type'] ?? 'code',
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
	$type = $line['type'] ?? 'code';
	
	switch ($type) {
		case 'filename':
			return mod_default_make_deprecations_scan_filename($line);
		case 'code_regex':
			return mod_default_make_deprecations_scan_code($line, false);
		default:
			return mod_default_make_deprecations_scan_code($line, true);
	}
}

/**
 * scan codebase for deprecated code patterns using grep
 *
 * @param array $line
 * @param bool $fixed_string if true, use -F flag for literal matching; if false, use -E for extended regex
 * @return array list of files where pattern was found
 */
function mod_default_make_deprecations_scan_code($line, $fixed_string = true) {
	$cms_dir = wrap_setting('cms_dir');
	$search_text = $line['search_text'];
	$exclude_pattern = $line['exclude_pattern'] ?? '';
	
	$extensions = wrap_setting('default_deprecations_extensions');
	// add dot prefix for grep
	$extensions = array_map(fn($ext) => '.'.$ext, $extensions);
	$exclude_files = wrap_setting('default_deprecations_exclude_files');
	$exclude_dirs = wrap_setting('default_deprecations_exclude_dirs');
	$include_params = array_map(fn($ext) => sprintf('--include="*%s"', $ext), $extensions);
	$exclude_params = array_map(fn($file) => sprintf('--exclude="%s"', $file), $exclude_files);
	$exclude_dir_params = array_map(fn($dir) => sprintf('--exclude-dir="%s"', $dir), $exclude_dirs);
	
	// use -F for fixed strings (literal), -E for extended regex
	$regex_flag = $fixed_string ? '-F' : '-E';
	
	$cmd = sprintf(
		'grep -r -l %s %s %s %s %s %s 2>/dev/null',
		$regex_flag,
		implode(' ', $include_params),
		implode(' ', $exclude_params),
		implode(' ', $exclude_dir_params),
		escapeshellarg($search_text),
		escapeshellarg($cms_dir)
	);
	
	exec($cmd, $output, $return_var);
	
	if ($return_var !== 0 || empty($output)) return [];
	
	$exclude_patterns = $exclude_pattern ? array_map('trim', explode(';', $exclude_pattern)) : [];
	$found = [];
	
	foreach ($output as $file) {
		$relative_path = str_replace($cms_dir.'/', '', $file);
		
		foreach ($exclude_patterns as $pattern) {
			if ($pattern && strpos($relative_path, $pattern) !== false) continue 2;
		}
		
		$found[]['file'] = $relative_path;
	}
	
	return $found;
}

/**
 * scan codebase for files matching a filename pattern
 *
 * @param array $line
 * @return array list of files where pattern was found
 */
function mod_default_make_deprecations_scan_filename($line) {
	$cms_dir = wrap_setting('cms_dir');
	$pattern = $line['search_text'];
	$exclude_pattern = $line['exclude_pattern'] ?? '';
	$found = [];
	
	$exclude_files = wrap_setting('default_deprecations_exclude_files');
	$exclude_patterns = $exclude_pattern ? array_map('trim', explode(';', $exclude_pattern)) : [];
	
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($cms_dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($iterator as $file) {
		if (!$file->isFile()) continue;
		
		$filename = $file->getFilename();
		
		// skip excluded files
		if (in_array($filename, $exclude_files)) continue;
		
		// make path relative to cms_dir for matching
		$relative_path = str_replace($cms_dir.'/', '', $file->getPathname());
		
		// check if path matches pattern (supports both filename and path patterns)
		$matched = fnmatch($pattern, $relative_path) || fnmatch($pattern, $filename);
		// also check if pattern with wildcards matches any part of the path
		if (!$matched && strpos($pattern, '/') !== false) {
			$matched = fnmatch('*/'.$pattern, $relative_path);
		}
		if (!$matched) continue;
		
		// skip files matching any exclusion pattern
		foreach ($exclude_patterns as $exclude) {
			if ($exclude && strpos($relative_path, $exclude) !== false) continue 2;
		}
		
		$found[]['file'] = $relative_path;
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


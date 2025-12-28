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
			return mod_default_make_deprecations_scan_regex($line);
		default:
			return mod_default_make_deprecations_scan_code($line);
	}
}

/**
 * get list of files to scan, filtered by extensions and exclusions
 *
 * @param string $exclude_pattern
 * @param bool $check_extensions whether to filter by file extension
 * @return array list of relative file paths
 */
function mod_default_make_deprecations_get_files($exclude_pattern = '', $check_extensions = true) {
	$cms_dir = wrap_setting('cms_dir');
	$files = [];
	
	$extensions = wrap_setting('default_deprecations_extensions');
	$exclude_files = wrap_setting('default_deprecations_exclude_files');
	$exclude_patterns = $exclude_pattern ? array_map('trim', explode(';', $exclude_pattern)) : [];
	
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($cms_dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($iterator as $file) {
		if (!$file->isFile()) continue;
		
		$filename = $file->getFilename();
		if (in_array($filename, $exclude_files)) continue;
		
		if ($check_extensions) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (!in_array($ext, $extensions)) continue;
		}
		
		$relative_path = str_replace($cms_dir.'/', '', $file->getPathname());
		
		// skip files matching any exclusion pattern
		foreach ($exclude_patterns as $pattern) {
			if ($pattern && strpos($relative_path, $pattern) !== false) continue 2;
		}
		
		$files[] = ['path' => $file->getPathname(), 'relative' => $relative_path];
	}
	
	return $files;
}

/**
 * scan codebase for deprecated code patterns using grep
 *
 * @param array $line
 * @return array list of files where pattern was found
 */
function mod_default_make_deprecations_scan_code($line) {
	$cms_dir = wrap_setting('cms_dir');
	$search_text = $line['search_text'];
	$exclude_pattern = $line['exclude_pattern'] ?? '';
	
	$extensions = wrap_setting('default_deprecations_extensions');
	// add dot prefix for grep
	$extensions = array_map(fn($ext) => '.'.$ext, $extensions);
	$exclude_files = wrap_setting('default_deprecations_exclude_files');
	$include_params = array_map(fn($ext) => sprintf('--include="*%s"', $ext), $extensions);
	$exclude_params = array_map(fn($file) => sprintf('--exclude="%s"', $file), $exclude_files);
	
	$cmd = sprintf(
		'grep -r -l %s %s %s %s 2>/dev/null',
		implode(' ', $include_params),
		implode(' ', $exclude_params),
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
 * scan codebase for deprecated code patterns using regex
 *
 * @param array $line
 * @return array list of files where pattern was found
 */
function mod_default_make_deprecations_scan_regex($line) {
	$regex_pattern = $line['search_text'];
	
	// validate regex pattern
	$valid = @preg_match($regex_pattern, '');
	if ($valid === false) {
		$message = sprintf(
			'Invalid regex pattern in %s. Regex patterns must have delimiters (e.g., /pattern/).',
			$line['identifier']
		);
		wrap_error($message);
		return [];
	}
	
	$files = mod_default_make_deprecations_get_files($line['exclude_pattern'] ?? '');
	$found = [];
	
	foreach ($files as $file) {
		$content = @file_get_contents($file['path']);
		if ($content === false) continue;
		
		if (preg_match($regex_pattern, $content)) {
			$found[]['file'] = $file['relative'];
		}
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
	$pattern = $line['search_text'];
	$files = mod_default_make_deprecations_get_files($line['exclude_pattern'] ?? '', false);
	$found = [];
	
	foreach ($files as $file) {
		$filename = basename($file['path']);
		if (fnmatch($pattern, $filename)) {
			$found[]['file'] = $file['relative'];
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


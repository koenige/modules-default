<?php 

/**
 * default module
 * log functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * get all used logfiles with standard error log format
 *
 * @return array
 */
function mf_default_log_files() {
	$logfiles = [];
	// PHP logfile?
	if ($php_log = ini_get('error_log')) {
		$php_log = realpath($php_log);
		$logfiles[$php_log]['path'] = $php_log;
		$logfiles[$php_log]['title'][] = 'PHP';
		$logfiles[$php_log]['types'][] = 'PHP';
	}
	// zzform, zzwrap logfiles?
	$levels = ['error', 'warning', 'notice'];
	foreach ($levels as $level) {
		if ($logfile = wrap_setting('error_log['.$level.']')) {
			$logfile = realpath($logfile);
			if (!$logfile) continue;
			$logfiles[$logfile]['path'] = $logfile;
			$logfiles[$logfile]['log'] = basename($logfile);
			$logfiles[$logfile]['title'][] = ucfirst($level);
			$logfiles[$logfile]['types'][] = 'zzform';
			$logfiles[$logfile]['types'][] = 'zzwrap';
		}
	}
	// module logfiles, extra logfiles?
	$extra_logfiles = array_merge(wrap_setting('modules'), wrap_setting('logfiles_custom'));
	foreach ($extra_logfiles as $log) {
		$logfile = sprintf('%s/%s.log', wrap_setting('log_dir'), $log);
		if (file_exists($logfile)) {
			$logfiles[$logfile]['path'] = $logfile;
			$logfiles[$logfile]['log'] = sprintf('%s.log', $log);
			$logfiles[$logfile]['title'][] = ucfirst($log);
			$logfiles[$logfile]['types'][] = $log;
		}
	}
	
	// make types unique
	foreach (array_keys($logfiles) as $logfile) {
		$logfiles[$logfile]['types'] = array_unique($logfiles[$logfile]['types']);
	}
	
	return $logfiles;
}

/**
 * format a single line from log
 *
 * @param string $line
 * @param array $types (optional)
 * @return array
 */
function mf_default_log_line($line, $types = []) {
	zzform_list_init();
	
	$out = [
		'type' => '',
		'user' => '',
		'date' => '',
		'level' => '',
		'time' => '',
		'status' => false
	];

	$line = trim($line);
	if (!$line) return [];

	// get date
	if (substr($line, 0, 1) === '[' AND $rightborder = strpos($line, ']')) {
		$out['date'] = substr($line, 1, $rightborder - 1);
		$line = substr($line, $rightborder + 2);
	}
	// get user
	if (substr($line, -1) === ']' AND strstr($line, '[')) {
		$out['user'] = substr($line, strrpos($line, '[')+1, -1);
		$out['user'] = explode(' ', $out['user']);
		if (count($out['user']) > 1 AND substr($out['user'][0], -1) === ':') {
			array_shift($out['user']); // get rid of User: or translations of it
		}
		$out['user'] = implode(' ', $out['user']);
		$line = substr($line, 0, strrpos($line, '['));
	}

	$tokens = explode(' ', $line);
	if ($tokens AND in_array($tokens[0], $types)) {
		$out['type'] = array_shift($tokens);
		$out['level'] = array_shift($tokens);
		if (substr($out['level'], -1) === ':') $out['level'] = substr($out['level'], 0, -1);
		else $out['level'] .= ' '.array_shift($tokens);
		if (substr($out['level'], -1) === ':') $out['level'] = substr($out['level'], 0, -1);
		$out['level_class'] = wrap_filename(strtolower($out['level']));
	}

	if (in_array($out['type'], ['zzform', 'zzwrap'])) {
		if (!$out['user'])
			$out['user'] = array_pop($tokens);
		$time = '';
		while (!$time) {
			// ignore empty tokens
			$time = trim(end($tokens));
			if (!$time) array_pop($tokens);
			if (!$tokens) break;
		}
		if (substr($time, 0, 1) === '{'
			AND substr($time, -1) === '}'
			AND is_numeric(substr($time, 1, -1))
		) {
			array_pop($tokens);
			$out['time'] = substr($time, 1, -1);
			// shorten time to make it more readable
			$out['time'] = substr($out['time'], 0, 6);
		}
	}

	if ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[0], -1) === ']') {
		$out['link'] = array_shift($tokens);
		$out['link'] = substr($out['link'], 1, -1);
		if (intval($out['link'])."" === $out['link']) {
			// e. g. 404 has no link repeated as it's already in the
			// error message	
			$out['status'] = $out['link'];
			$out['link'] = false;
		}
	} elseif ($tokens AND substr($tokens[0], 0, 1) === '[' AND substr($tokens[1], -1) === ']'
		AND strlen($tokens[0]) === 4) {
		$out['status'] = array_shift($tokens);
		$out['status'] = substr($out['status'], 1);
		$out['link'] = array_shift($tokens);
		$out['link'] = substr($out['link'], 0, -1);
	} else {
		$out['link'] = false;
	}
	$out['error'] = str_replace('<', '&lt;', implode(' ', $tokens));

	$post = false;
	if (substr($out['error'], 0, 11) === 'POST[json] ') {
		$post = @json_decode(substr($out['error'], 11));
		if ($post)
			$out['error'] = 'POST '.wrap_print($post);
	}
	if (!$post) {
		$no_html = false;
		if (in_array($out['type'], ['zzform', 'zzwrap']))
			$no_html = true;
		$out['error'] = mf_default_log_split($out['error'], $no_html);
	}
	// htmlify links
	if (stristr($out['error'], 'http:/<wbr>/<wbr>') OR stristr($out['error'], 'https:/<wbr>/<wbr>')) {
		$out['error'] = preg_replace_callback('~(\S+):/<wbr>/<wbr>(\S+)~', 'mf_default_log_url', $out['error']);
	}
	$out['error'] = str_replace(',', ', ', $out['error']);
	$out['error'] = zz_list_word_split($out['error']);
	$out['error'] = zz_mark_search_string($out['error']);
	$out['error'] = str_replace('%%%', '\%\%\%', $out['error']);

	$out['date_begin'] = $out['date'];
	if ($out['link']) {
		$out['links'] = sprintf('[<a href="%s">%s</a>]<br>'
			, str_replace('&', '&amp;', wrap_html_escape($out['link']))
			, mf_default_log_split($out['link'], true)
		);
	} else {
		$out['links'] = '';
	}
	return $out;
}

/**
 * get rid of long lines with zero width space (<wbr>) - &shy; does
 * not work at least in firefox 3.6 with slashes
 *
 * @param string $string
 * @param bool $no_html
 * @return string
 */
function mf_default_log_split($string, $no_html) {
	if ($no_html) {
		$string = str_replace('<', '&lt;', $string);
	}
	$string = str_replace(';', ';<wbr>', $string);
	$string = str_replace('&', '<wbr>&amp;', $string);
	$string = str_replace('&amp;#8203;', '<wbr>', $string);
	$string = str_replace('/', '/<wbr>', $string);
	$string = str_replace('=', '=<wbr>', $string);
	$string = str_replace('%', '<wbr>%', $string);
	$string = str_replace('-at-', '<wbr>-at-', $string);
	return $string;
}

/**
 * create anchor element for each URL in log
 *
 * @param array $array
 * @return string
 */
function mf_default_log_url($array) {
	$href = str_replace('<wbr>', '', $array[0]);
	$linktext = $array[0];
	$link = sprintf('<a href="%s">%s</a>', $href, $linktext); 
	return $link;
}

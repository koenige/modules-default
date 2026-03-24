<?php 

/**
 * default module
 * common functions for module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * checks if 'search' is active, then tests string against search string
 *
 * @param string $string
 * @return bool false: no search active or string not found; true: search is
 *		active and string was found
 */
function mf_default_searched($string) {
	if (empty($_GET['q'])) return false;
	if (stristr($string, $_GET['q'])) return true;
	if (stristr(urldecode($string), $_GET['q'])) return true;

	// allow for searching ignoring replaced zero width space
	// PHPs char does not support unicode
	$q = urlencode($_GET['q']);
	if (wrap_setting('character_set') === 'utf-8') {
		$char8203 = '%E2%80%8B';
	} else {
		// this does not work for all other character sets
		// but it works for iso-8859-1 and -2
		$char8203 = '%26%238203%3B';
	}
	$q = str_replace($char8203, '', $q);
	$q = urldecode($q);
	$_GET['q'] = $q;
	if (stristr($string, $q)) return true;
	return false;
}

/**
 * HTML output of form with button to delete all lines, files etc. in list
 * 'q'-search filter will be regarded
 *
 * @return array
 */
function mf_default_delete_all_form() {
	if (!empty($_POST['deleteall'])) return ['', ''];
	if (!isset($_GET['deleteall'])) return ['', ''];

	zzform_url_remove(['deleteall']);
	$url = zzform_url('full+qs_zzform');
	return [$url, !empty($_GET['q']) ? wrap_html_escape($_GET['q']) : ''];
}

/**
 * check if caching should be disabled
 *
 * Any reference instant (file mtime, strtotime(js_css_nocache)) within
 * max-age counts. max-age from filetypes or cache_control_text (CSS/JS).
 *
 * @param string|false $ext filetype key for wrap_filetypes (e.g. css), or false to skip filetypes and use cache_control_text only
 * @param int|null $mtime unix mtime of the file if known; null or omit otherwise (e.g. from mf_default_filemtime())
 * @return bool
 */
function mf_default_nocache($ext = false, $mtime = NULL) {
	$reference_times = [];
	if ($mtime) {
		$reference_times[] = $mtime;
	}
	if (wrap_setting('js_css_nocache')
		AND $setting = strtotime(wrap_setting('js_css_nocache'))) {
		$reference_times[] = $setting;
	}
	if ($ext) {
		$filetype_cfg = wrap_filetypes($ext);
		$max_age = $filetype_cfg['max-age'] ?? NULL;
	} else {
		$max_age = NULL;
	}
	if (!isset($max_age))
		$max_age = wrap_setting('cache_control_text');

	foreach ($reference_times as $time) {
		if ($time + $max_age >= time()) return true;
	}
	return false;
}

/**
 * last modification time of a file (or first path in an array from wrap_collect_files)
 *
 * @param string|array $file filesystem path, or single-element path list
 * @return int|null unix time from filemtime(), or null if unreadable
 */
function mf_default_filemtime($file) {
	if (is_array($file)) $file = reset($file);
	if (!is_readable($file)) return NULL;
	return filemtime($file);
}

<?php 

/**
 * default module
 * common functions for module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * checks if 'search' is active, then tests string against search string
 *
 * @param string $string
 * @return bool false: no search active or string not found; true: search is
 *		active and string was found
 */
function zz_maintenance_searched($string) {
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
 * @global array $zz_conf
 * @return array
 */
function zz_maintenance_deleteall_form() {
	global $zz_conf;
	if (!empty($_POST['deleteall'])) return ['', ''];
	if (!isset($_GET['deleteall'])) return ['', ''];

	$unwanted_keys = ['deleteall'];
	$qs = zz_edit_query_string($zz_conf['int']['url']['qs_zzform'], $unwanted_keys);
	$url = $zz_conf['int']['url']['full'].$qs;
	return [$url, !empty($_GET['q']) ? wrap_html_escape($_GET['q']) : ''];
}

/**
 * initialize variables and include files to use zz_list() for maintenance
 *
 */
function zz_maintenance_list_init() {
	global $zz_conf;
	static $init = false;
	if ($init) return; // just once

	// zz_edit_query_string(), zz_get_url_self()
	wrap_include('functions', 'zzform');
	// zz_init_limit()
	wrap_include('output', 'zzform');
	// zz_mark_search_string(), zz_list_total_records(), zz_list_pages()
	wrap_include('list', 'zzform');
	// zz_search_form()
	wrap_include('searchform', 'zzform');

	wrap_setting('zzform_search', 'bottom');

	$zz_conf['int']['url'] = zz_get_url_self();
	zz_init_limit();
	$init = true;
}

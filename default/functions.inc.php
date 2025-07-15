<?php 

/**
 * default module
 * common functions for module
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2025 Gustaf Mossakowski
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

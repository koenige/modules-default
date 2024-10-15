<?php

/**
 * default module
 * delimiter for query string, either ? or &
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * delimiter for query string, either ? or &
 *
 * @param array $params -
 * @return array $page
 */
function page_qsdelimiter() {
	$qs = parse_url(wrap_setting('request_uri'), PHP_URL_QUERY);
	if ($qs) return '&amp;';
	return '?';
}

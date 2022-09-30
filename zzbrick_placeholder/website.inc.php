<?php 

/**
 * default module
 * placeholder script for website
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_placeholder_website($brick) {
	if (empty($brick['parameter'])) return $brick;
	
	$sql = 'SELECT website_id, website, domain
		FROM websites
		WHERE domain = "%s"';
	$sql = sprintf($sql, wrap_db_escape($brick['parameter']));
	$website = wrap_db_fetch($sql);
	if (!$website) wrap_quit(404);

	// @todo breadcrumbs

	$brick['data'] = $website;
	return $brick;
}

<?php 

/**
 * default module
 * placeholder script for website
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_placeholder_website($brick) {
	if (empty($brick['placeholder'])) return $brick;
	
	$sql = 'SELECT website_id, website, domain
		FROM websites
		WHERE domain = "%s"';
	$sql = sprintf($sql, wrap_db_escape($brick['placeholder']));
	$website = wrap_db_fetch($sql);
	if (!$website) wrap_quit(404);

	// make settings of website available for this backend
	wrap_setting('backend_website_id', $website['website_id']);
	wrap_setting('backend_path', $brick['placeholder']);
	wrap_setting_backend();
	// make paths of website available for this backend
	wrap_setting('path_website_id', $website['website_id']);

	wrap_page_meta('access', sprintf('website_id:%d', $website['website_id']));
	wrap_access_page();

	// breadcrumbs
	wrap_page_meta('breadcrumb_placeholder', [
		'title' => $website['domain'],
		'url_path' => $website['domain']
	]);
	
	$brick['data'] = $website;
	return $brick;
}

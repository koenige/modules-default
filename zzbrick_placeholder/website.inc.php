<?php 

/**
 * default module
 * placeholder script for website
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_placeholder_website($brick) {
	global $zz_page;
	global $zz_setting;
	if (empty($brick['placeholder'])) return $brick;
	
	$sql = 'SELECT website_id, website, domain
		FROM websites
		WHERE domain = "%s"';
	$sql = sprintf($sql, wrap_db_escape($brick['placeholder']));
	$website = wrap_db_fetch($sql);
	if (!$website) wrap_quit(404);

	// make settings of website available for this backend
	$zz_setting['backend_website_id'] = $website['website_id'];
	$zz_setting['backend_path'] = $brick['placeholder'];
	wrap_setting_backend();

	// breadcrumbs
	if (!str_ends_with($zz_page['url']['full']['path'], sprintf('%s/', $website['domain'])))
		$brick['page']['breadcrumbs'][] = sprintf(
			'<a href="./">%s</a>', $website['domain'], $website['domain']
		);

	$brick['data'] = $website;
	return $brick;
}

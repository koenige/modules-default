<?php 

/**
 * default module
 * administration of a website
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_website($params, $settings, $data) {
	if (count($params) !== 1) return false;

	$page['title'] = $data['domain'];
	$page['breadcrumbs'][] = $data['domain'];
	$page['text'] = wrap_template('website', $data);
	return $page;
}

<?php 

/**
 * default module
 * Help texts, overview
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_helptext($params) {
	$data = brick_request_data('helptexts', [$params[0]]);
	if (!$data) return false;
	
	switch ($data['type']) {
	case 'md':
		$page['text'] = markdown($data['text']);
		$page['dont_show_h1'] = true;
		break;
	default:
		$page['text'] = sprintf('<pre>%s</pre>', $data['text']);
		break;
	}
	$page['breadcrumbs'][]['title'] = $data['title'];
	$page['title'] = $data['title'];
	// @todo add link to help overview page
	$page['text'] = sprintf('<div class="helptext">%s</div>', $page['text']);
	$page['extra']['css'][] = 'default/help.css';
	return $page;
}

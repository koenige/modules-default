<?php 

/**
 * default module
 * Help texts, overview
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_helptext($params) {
	$data = brick_request_data('helptexts', [$params[0]]);
	if (!$data) return false;
	
	$data['text'] = file_get_contents($data['filename']);
	$data['text'] = preg_replace('/<!--[\s\S]*?-->/', '', $data['text']);

	switch ($data['type']) {
	case 'md':
		$page['text'] = markdown($data['text']);
		$page['dont_show_h1'] = true;
		break;
	default:
		$page['text'] = sprintf('<pre>%s</pre>', $data['text']);
	}
	// @todo add link to help overview page
	return $page;
}

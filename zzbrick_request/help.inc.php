<?php 

/**
 * default module
 * Help texts, overview
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * display a help text
 *
 * @param array $params
 *		[0]: package
 *		[1]: identifier of help text
 * @return array|false
 */
function mod_default_help($params) {
	if (count($params) !== 2) return false;

	$data = brick_request_data('help', [$params[0], $params[1]]);
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
	if (wrap_path('default_help_package', [], ['testing' => 1])) {
		$page['breadcrumbs'][] = [
			'title' => $data['package'],
			'url_path' => wrap_path('default_help_package', $data['package'])
		];
	}
	$page['breadcrumbs'][]['title'] = $data['title'];
	$page['title'] = $data['title'];
	// @todo add link to help overview page
	$page['text'] = sprintf('<div class="helptext">%s</div>', $page['text']);
	$page['extra']['css'][] = 'default/help.css';
	return $page;
}

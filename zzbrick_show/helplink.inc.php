<?php 

/**
 * default module
 * link to help text
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * link to help text
 * 
 * examples: 
 * 		%%% helplink Name of the help text %%% 
 * @param array $params
 * @return array
 */
function mod_default_show_helplink($params, $settings) {
	if (!$params) return [];
	if (!wrap_path('default_help', [], ['testing' => true])) return [];

	$filename = strtolower(implode('-', $params));

	wrap_include('request', 'zzbrick');
	$data = brick_request_data('help', [$filename]);
	if (!$data) return [];

	$page['text'] = wrap_template('helplink', $data);
	return $page;
}

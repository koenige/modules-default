<?php 

/**
 * default module
 * Help: list of texts per package
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * overview of help texts per package
 *
 * @param array $params
 * @return array|false
 */
function mod_default_help_package($params) {
	wrap_include('zzbrick_request_get/help', 'default');
	if (count($params) !== 1) return false;

	$data = [];
	$data['package'] = $params[0];
	$data['texts'] = mf_default_help_list($data['package']);
	if (!$data['texts']) return false;
	$pkg = wrap_cfg_files('package', ['package' => $data['package'], 'translate' => true]);
	$data['name'] = $pkg['about']['name'] ?? $data['package'];

	$page['extra']['css'][] = 'default/help.css';
	$page['breadcrumbs'][] = ['title' => $data['name']];
	$page['title'] = wrap_text('Help for %s', ['values' => [$data['name']]]);
	$page['text'] = wrap_template('help-package', $data);
	return $page;
}

<?php 

/**
 * default module
 * Help: list of packages with help texts
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * overview of packages with help texts
 *
 * @param array $params
 * @return array|false
 */
function mod_default_help_packages($params) {
	wrap_include('zzbrick_request_get/help', 'default');

	$data = [];
	$data['packages'] = mf_default_help_packages();
	$page['extra']['css'][] = 'default/help.css';
	$page['breadcrumbs'][] = ['title' => wrap_text('Help')];
	$page['title'] = wrap_text('Help');
	$page['text'] = wrap_template('help-packages', $data);
	return $page;
}

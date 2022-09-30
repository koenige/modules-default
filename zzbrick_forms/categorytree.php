<?php

/**
 * default module
 * Database form for a tree of categories
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!$category_id = wrap_category_id($brick['vars'][0]))
	wrap_quit(404);

// @todo add access restrictions

$zz = zzform_include_table('categories');
if (!empty($brick['local_settings']['title']))
	$zz['title'] = $brick['local_settings']['title'];

$zz['fields'][4]['show_hierarchy_subtree'] = $category_id;
$zz['fields'][4]['show_hierarchy_use_top_value_instead_NULL'] = true;

if ($brick['local_settings']['hide_identifier']) {
	$zz['fields'][5]['hide_in_list'] = true;
	if (!empty($zz['fields'][7]))
		$zz['fields'][7]['hide_in_list'] = true;
}

$zz['list']['hierarchy']['id'] = $category_id;
$zz['list']['hierarchy']['hide_top_value'] = true;

unset($zz['filter']);

// @todo details

if (!empty($brick['local_settings']['set_redirect_old']))
	$zz['set_redirect'][] = [
		'old' => $brick['local_settings']['set_redirect_old'],
		'new' => $brick['local_settings']['set_redirect_new'] ?? $brick['local_settings']['set_redirect_old'],
		'field_name' => $brick['local_settings']['set_redirect_field'] ?? 'path'
	];

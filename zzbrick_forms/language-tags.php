<?php

/**
 * default module
 * Database form for website languages
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


wrap_access_quit('default_settings');

$zz = zzform_include('languages-categories');

$zz['title'] = 'Languages';
$zz['where']['type_category_id'] = wrap_category_id('language/register');

foreach ($zz['fields'] as $no => $field) {
	$identifier = zzform_field_identifier($field);
	if (!$identifier) continue;
	
	switch ($identifier) {
		case 'type_category_id':
			$zz['fields'][$no]['hide_in_list'] = true;
			$zz['fields'][$no]['hide_in_form'] = true;
			$zz['fields'][$no]['type'] = 'hidden';
			$zz['fields'][$no]['type_detail'] = 'select';
			break;
		case 'category_id':
			$zz['fields'][$no]['title'] = 'Register';
			$zz['fields'][$no]['show_hierarchy_subtree'] = wrap_category_id('language/register');
			$zz['fields'][$no]['default'] = wrap_category_id('language/register/neutral');
			break;
	}
}

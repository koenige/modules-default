<?php 

/**
 * default module
 * Table definition for detail tables '_categories'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['type'] = 'id';

// foreign key
$zz['fields'][2]['type'] = 'select';

$zz['fields'][6]['title_tab'] = 'Seq.';
$zz['fields'][6]['field_name'] = 'sequence';
$zz['fields'][6]['type'] = 'number';
$zz['fields'][6]['hide_in_list_if_empty'] = true;
$zz['fields'][6]['for_action_ignore'] = true;

$zz['fields'][3]['field_name'] = 'category_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT category_id, category, main_category_id
	FROM categories
	ORDER BY sequence';
$zz['fields'][3]['show_hierarchy'] = 'main_category_id';
$zz['fields'][3]['id_field_name'] = 'category_id';
$zz['fields'][3]['display_field'] = 'category';
$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][4]['field_name'] = 'property';

$zz['fields'][5]['field_name'] = 'type_category_id';
$zz['fields'][5]['type'] = 'hidden';
$zz['fields'][5]['type_detail'] = 'select';
$zz['fields'][5]['hide_in_form'] = true;
$zz['fields'][5]['hide_in_list'] = true;
$zz['fields'][5]['exclude_from_search'] = true;
$zz['fields'][5]['for_action_ignore'] = true;

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

<?php 

/**
 * default module
 * Table definition for 'categories'
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Categories';
$zz['table'] = '/*_PREFIX_*/categories';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'category_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'category';
$zz['fields'][2]['list_append_next'] = true;

$zz['fields'][3]['field_name'] = 'description';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['list_prefix'] = '<p><small>';
$zz['fields'][3]['list_suffix'] = '</small></p>';
//$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][4]['title'] = 'Main Category';
$zz['fields'][4]['field_name'] = 'main_category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT category_id, category as main_category, path, main_category_id 
	FROM /*_PREFIX_*/categories ORDER BY sequence, main_category';
$zz['fields'][4]['display_field'] = 'main_category';
$zz['fields'][4]['search'] = 'cat.category';
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['hide_in_list'] = true;
$zz['fields'][4]['show_hierarchy_same_table'] = true;

$zz['fields'][5]['title'] = 'Identifier';
$zz['fields'][5]['field_name'] = 'path';
$zz['fields'][5]['type'] = 'identifier';
$zz['fields'][5]['fields'] = array('main_category_id[path]', 'category');
//$zz['fields'][5]['hide_in_list'] = true;
$zz['fields'][5]['conf_identifier'] = array('concat' => '/');

$zz['fields'][6]['field_name'] = 'sequence';
$zz['fields'][6]['title_tab'] = 'Seq.';
$zz['fields'][6]['type'] = 'number';
$zz['fields'][6]['auto_value'] = 'increment';
$zz['fields'][6]['class'] = 'hidden480';

// restrict access to this field if needed
$zz['fields'][7]['field_name'] = 'parameters';

$zz['fields'][20]['title'] = 'Updated';
$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/categories.*,
	cat.category AS main_category
	FROM /*_PREFIX_*/categories
	LEFT JOIN /*_PREFIX_*/categories AS cat
		ON (/*_PREFIX_*/categories.main_category_id = cat.category_id)'; 
$zz['sqlorder'] = ' ORDER BY sequence, /*_PREFIX_*/categories.path';

$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][4]['field_name'];
$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];

$zz['filter'][1]['sql'] = 'SELECT category_id, category
	FROM /*_PREFIX_*/categories
	WHERE ISNULL(main_category_id)
	ORDER BY category';
$zz['filter'][1]['title'] = wrap_text('Main Category');
$zz['filter'][1]['identifier'] = 'maincategory';
$zz['filter'][1]['type'] = 'show_hierarchy';
$zz['filter'][1]['field_name'] = 'main_category_id';

$zz_conf['max_select'] = 200;
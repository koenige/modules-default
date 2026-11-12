<?php 

/**
 * default module
 * Table definition for 'categories/text'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Category Texts';
$zz['table'] = '/*_PREFIX_*/categories_text';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'category_text_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Category';
$zz['fields'][2]['field_name'] = 'category_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT category_id, category, main_category_id
	FROM /*_PREFIX_*/categories
	ORDER BY sequence, category';
$zz['fields'][2]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][2]['display_field'] = 'category';
$zz['fields'][2]['search'] = '/*_PREFIX_*/categories.category';
$zz['fields'][2]['character_set'] = 'utf8';
$zz['fields'][2]['show_hierarchy'] = 'main_category_id';
$zz['fields'][2]['if']['where']['hide_in_list'] = true;
$zz['fields'][2]['if']['where']['hide_in_form'] = true;

$zz['fields'][3]['title'] = 'Type';
$zz['fields'][3]['field_name'] = 'text_category_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT category_id, category, main_category_id
	FROM /*_PREFIX_*/categories
	ORDER BY sequence, category';
$zz['fields'][3]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][3]['display_field'] = 'text_category';
$zz['fields'][3]['search'] = 'text_categories.category';
$zz['fields'][3]['character_set'] = 'utf8';
$zz['fields'][3]['show_hierarchy'] = 'main_category_id';
$zz['fields'][3]['show_hierarchy_subtree'] = wrap_category_id('texts');

$zz['fields'][4]['title'] = 'Text';
$zz['fields'][4]['field_name'] = 'category_text';
$zz['fields'][4]['type'] = 'text';
$zz['fields'][4]['translation']['hide_in_list'] = false;
$zz['fields'][4]['maxlength'] = 255;
$zz['fields'][4]['typo_cleanup'] = true;
$zz['fields'][4]['typo_remove_double_spaces'] = true;

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;


$zz['sql'] = 'SELECT /*_PREFIX_*/categories_text.*
		, /*_PREFIX_*/categories.category
		, text_categories.category AS text_category
	FROM /*_PREFIX_*/categories_text
	LEFT JOIN /*_PREFIX_*/categories
		ON /*_PREFIX_*/categories_text.category_id = /*_PREFIX_*/categories.category_id
	LEFT JOIN /*_PREFIX_*/categories text_categories
		ON /*_PREFIX_*/categories_text.text_category_id = text_categories.category_id
';
$zz['sqlorder'] = ' ORDER BY /*_PREFIX_*/categories.path, text_categories.sequence, text_categories.category';
$zz['sql_translate'] = ['category_id' => 'categories', 'text_category_id' => 'categories'];

$zz['subselect']['sql'] = 'SELECT /*_PREFIX_*/categories_text.category_id
		, text_category_id, text_categories.category AS text_category, category_text
	FROM /*_PREFIX_*/categories_text
	LEFT JOIN /*_PREFIX_*/categories text_categories
		ON /*_PREFIX_*/categories_text.text_category_id = text_categories.category_id';
$zz['subselect']['sql_ignore'] = ['text_category_id'];
$zz['subselect']['sql_translate'] = ['text_category_id' => 'categories'];
$zz['subselect']['concat_fields'] = ': ';

$zz['subtitle']['category_id']['sql'] = $zz['fields'][2]['sql'];
$zz['subtitle']['category_id']['var'] = ['category'];

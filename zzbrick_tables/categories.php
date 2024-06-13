<?php 

/**
 * default module
 * Table definition for 'categories'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2011, 2016-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Categories';
$zz['table'] = '/*_PREFIX_*/categories';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'category_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'category';
$zz['fields'][2]['list_append_next'] = true;
$zz['fields'][2]['typo_cleanup'] = true;
$zz['fields'][2]['typo_remove_double_spaces'] = true;

$zz['fields'][22]['title'] = 'Abbreviation';
$zz['fields'][22]['title_tab'] = 'Abbr.';
$zz['fields'][22]['field_name'] = 'category_short';
$zz['fields'][22]['type'] = 'text';
$zz['fields'][22]['list_append_next'] = true;
$zz['fields'][22]['list_prefix'] = '<br><em>(';
$zz['fields'][22]['list_suffix'] = ')</em> ';

$zz['fields'][3]['field_name'] = 'description';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['list_prefix'] = '<p><small>';
$zz['fields'][3]['list_suffix'] = '</small></p>';
$zz['fields'][3]['typo_cleanup'] = true;

$zz['fields'][4]['title'] = 'Main Category';
$zz['fields'][4]['field_name'] = 'main_category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT category_id, category, path, main_category_id 
	FROM /*_PREFIX_*/categories ORDER BY sequence, category';
$zz['fields'][4]['key_field_name'] = 'category_id';
$zz['fields'][4]['display_field'] = 'main_category';
$zz['fields'][4]['search'] = 'cat.category';
$zz['fields'][4]['character_set'] = 'utf8';
$zz['fields'][4]['hide_in_list'] = true;
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['show_hierarchy_same_table'] = true;
$zz['fields'][4]['sql_translate'] = ['category_id' => 'categories'];

$zz['fields'][5]['title'] = 'Identifier';
$zz['fields'][5]['title_tab'] = 'Identifier / Parameters';
$zz['fields'][5]['field_name'] = 'path';
$zz['fields'][5]['type'] = 'identifier';
$zz['fields'][5]['fields'] = ['main_category_id[path]', 'category_short', 'category'];
$zz['fields'][5]['identifier']['concat'] = '/';
$zz['fields'][5]['identifier']['strip_tags'] = true;
$zz['fields'][5]['identifier']['ignore_this_if']['category'] = 'category_short';
$zz['fields'][5]['identifier']['max_length'] = 24; // because of hierarchies
$zz['fields'][5]['list_suffix'] = '<br>';

$zz['fields'][7]['field_name'] = 'parameters';
$zz['fields'][7]['type'] = 'parameter';
$zz['fields'][7]['explanation'] = 'Internal parameters. Change with care.';

if (wrap_access('default_categories_parameters')) {
	$zz['fields'][5]['list_append_next'] = true;
} else {
	$zz['fields'][7]['hide_in_form'] = true;
	$zz['fields'][7]['hide_in_list'] = true;
}

$zz['fields'][6]['field_name'] = 'sequence';
$zz['fields'][6]['title_tab'] = 'Seq.';
$zz['fields'][6]['type'] = 'number';
$zz['fields'][6]['class'] = 'hidden480';

// extra enum field if needed
$zz['fields'][8] = [];

$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/categories.*
		, cat.category AS main_category
		, SUBSTRING(/*_PREFIX_*/categories.path, LENGTH(SUBSTRING_INDEX(/*_PREFIX_*/categories.path, "/", 1)) + 2) AS path_level1
	FROM /*_PREFIX_*/categories
	LEFT JOIN /*_PREFIX_*/categories AS cat
		ON (/*_PREFIX_*/categories.main_category_id = cat.category_id)'; 
$zz['sqlorder'] = ' ORDER BY /*_PREFIX_*/categories.sequence, /*_PREFIX_*/categories.path';

$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][4]['field_name'];
$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];

$zz['filter'][1]['sql'] = 'SELECT category_id, category
	FROM /*_PREFIX_*/categories
	WHERE ISNULL(main_category_id)
	ORDER BY category';
$zz['filter'][1]['sql_translate'] = ['category_id' => 'categories'];
$zz['filter'][1]['title'] = wrap_text('Main Category');
$zz['filter'][1]['identifier'] = 'maincategory';
$zz['filter'][1]['type'] = 'show_hierarchy';
$zz['filter'][1]['field_name'] = 'main_category_id';

$zz['sql_translate'] = ['category_id' => 'categories'];

$zz['setting']['zzform_max_select'] = 200;
$zz['record']['copy'] = true;

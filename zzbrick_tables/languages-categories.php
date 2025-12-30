<?php 

/**
 * default module
 * Table definition for languages_categories
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Language Categories';
$zz['table'] = '/*_PREFIX_*/languages_categories';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'language_category_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Language';
$zz['fields'][2]['field_name'] = 'language_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT language_id, IFNULL(iso_639_1, iso_639_2t), language
	FROM /*_PREFIX_*/languages
	ORDER BY language';
$zz['fields'][2]['sql_translate'] = ['language_id' => 'languages'];
$zz['fields'][2]['display_field'] = 'language';
$zz['fields'][2]['search'] = '/*_PREFIX_*/languages.language';

$zz['fields'][3]['title'] = 'Type';
$zz['fields'][3]['field_name'] = 'type_category_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT category_id, category
	FROM /*_PREFIX_*/categories
	WHERE main_category_id IS NULL
	ORDER BY sequence, category';
$zz['fields'][3]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][3]['display_field'] = 'type_category';

$zz['fields'][4]['title'] = 'Category';
$zz['fields'][4]['field_name'] = 'category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT category_id, category, main_category_id
	FROM /*_PREFIX_*/categories
	ORDER BY main_category_id, sequence, category';
$zz['fields'][4]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['display_field'] = 'category';

$zz['fields'][5]['title'] = 'Label';
$zz['fields'][5]['field_name'] = 'label';
$zz['fields'][5]['type'] = 'text';
$zz['fields'][5]['maxlength'] = 50;
$zz['fields'][5]['explanation'] = 'Short label in target language (e.g. “Du”, “Sie”, “tu”, “vous”)';

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/languages_categories.*
		, /*_PREFIX_*/languages.language_id, /*_PREFIX_*/languages.language
		, type_cat.category AS type_category
		, cat.category_id, cat.category
	FROM /*_PREFIX_*/languages_categories
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)
	LEFT JOIN /*_PREFIX_*/categories type_cat 
		ON /*_PREFIX_*/languages_categories.type_category_id = type_cat.category_id
	LEFT JOIN /*_PREFIX_*/categories cat 
		ON /*_PREFIX_*/languages_categories.category_id = cat.category_id
';
$zz['sql_translate'] = ['language_id' => 'languages', 'category_id' => 'categories'];
$zz['sqlorder'] = ' ORDER BY language, type_category, category';

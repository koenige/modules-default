<?php 

/**
 * default module
 * Table definition for languages_categories
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2025-2026 Gustaf Mossakowski
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
$zz['fields'][2]['sql'] = 'SELECT language_id, IFNULL(iso_639_1, iso_639_2t) AS language_code, language
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
	WHERE main_category_id = /*_ID categories language _*/
	ORDER BY sequence, category';
$zz['fields'][3]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][3]['display_field'] = 'type_category';
$zz['fields'][3]['exclude_from_search'] = true;
$zz['fields'][3]['hide_in_form'] = true;

$zz['fields'][7]['field_name'] = 'country_id';
$zz['fields'][7]['type'] = 'select';
$zz['fields'][7]['sql'] = 'SELECT country_id, country_code, country
	FROM /*_PREFIX_*/countries
	ORDER BY country';
$zz['fields'][7]['sql_translate'] = ['country_id' => 'countries'];
$zz['fields'][7]['sql_character_set'][1] = 'latin1';
$zz['fields'][7]['sql_character_set'][2] = 'latin1';
$zz['fields'][7]['search'] = '/*_PREFIX_*/countries.country';
$zz['fields'][7]['hide_in_list_if_empty'] = true;
$zz['fields'][7]['display_field'] = 'country_code';

$zz['fields'][4]['title'] = 'Category';
$zz['fields'][4]['field_name'] = 'category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT category_id, category, main_category_id
		, SUBSTRING_INDEX(SUBSTRING_INDEX(parameters, "&tag=", -1), "&", 1) AS tag
	FROM /*_PREFIX_*/categories
	ORDER BY main_category_id, sequence, category';
$zz['fields'][4]['sql_translate'] = ['category_id' => 'categories'];
$zz['fields'][4]['sql_ignore'] = ['tag'];
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['display_field'] = 'category';
$zz['fields'][4]['search'] = '/*_PREFIX_*/categories.category';

$zz['fields'][5]['title'] = 'Label';
$zz['fields'][5]['field_name'] = 'label';
$zz['fields'][5]['placeholder'] = true;
$zz['fields'][5]['type'] = 'text';
$zz['fields'][5]['maxlength'] = 50;
$zz['fields'][5]['explanation'] = 'Short label in target language (e.g. “Du”, “Sie”, “tu”, “vous”)';

$zz['fields'][6]['title'] = 'Language Tag';
$zz['fields'][6]['field_name'] = 'language_tag';
$zz['fields'][6]['type'] = 'identifier';
$zz['fields'][6]['fields'] = ['language_id[language_code]', 'country_id[country_code]', 'category_id[tag]'];
$zz['fields'][6]['identifier']['lowercase'] = false;
$zz['fields'][6]['identifier']['concat'] = '-';

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;


$zz['sql'] = 'SELECT /*_PREFIX_*/languages_categories.*
		, /*_PREFIX_*/languages.language_id, /*_PREFIX_*/languages.language
		, type_cat.category AS type_category
		, /*_PREFIX_*/categories.category_id, /*_PREFIX_*/categories.category
		, /*_PREFIX_*/languages.iso_639_1, /*_PREFIX_*/languages.iso_639_2t
		, country_code
	FROM /*_PREFIX_*/languages_categories
	LEFT JOIN /*_PREFIX_*/languages USING (language_id)
	LEFT JOIN /*_PREFIX_*/categories type_cat 
		ON /*_PREFIX_*/languages_categories.type_category_id = type_cat.category_id
	LEFT JOIN /*_PREFIX_*/categories
		ON /*_PREFIX_*/languages_categories.category_id = /*_PREFIX_*/categories.category_id
	LEFT JOIN /*_PREFIX_*/countries USING (country_id)
';
$zz['sql_translate'] = ['language_id' => 'languages', 'category_id' => 'categories'];
$zz['sqlorder'] = ' ORDER BY language, type_category, category';

$zz['subselect']['sql'] = 'SELECT language_id, category_id, category, label
	FROM /*_PREFIX_*/languages_categories
	LEFT JOIN /*_PREFIX_*/categories USING (category_id)';
$zz['subselect']['sql_ignore'] = ['category_id'];
$zz['subselect']['sql_translate'] = ['category_id' => 'categories'];

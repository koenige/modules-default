<?php 

/**
 * default module
 * Table definition for 'webpages/categories'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz = zzform_include('categories.template');

$zz['title'] = 'Webpages/Categories';
$zz['table'] = '/*_PREFIX_*/webpages_categories';

$zz['fields'][1]['field_name'] = 'page_category_id';

$zz['fields'][2]['field_name'] = 'page_id';
$zz['fields'][2]['sql'] = 'SELECT page_id, identifier, title, mother_page_id
	FROM /*_PREFIX_*/webpages
	ORDER BY sequence';
$zz['fields'][2]['display_field'] = 'identifier';
$zz['fields'][2]['search'] = '/*_PREFIX_*/webpages.identifier';
$zz['fields'][2]['show_hierarchy'] = 'mother_page_id';

// category_id
$zz['fields'][3]['show_hierarchy_subtree'] = wrap_category_id('menu');

// type_category_id
$zz['fields'][5]['value'] = wrap_category_id('menu');


$zz['sql'] = 'SELECT /*_PREFIX_*/webpages_categories.*
		, /*_PREFIX_*/webpages.identifier
	FROM /*_PREFIX_*/webpages_categories
	LEFT JOIN /*_PREFIX_*/webpages USING (page_id)
	LEFT JOIN /*_PREFIX_*/categories USING (category_id)
';
$zz['sqlorder'] = ' ORDER BY identifier, /*_PREFIX_*/webpages_categories.sequence, /*_PREFIX_*/categories.sequence';

$zz['subselect']['sql'] = 'SELECT page_id, category_id, category_short
	FROM /*_PREFIX_*/webpages_categories
	LEFT JOIN /*_PREFIX_*/categories USING (category_id)';
$zz['subselect']['sql_translate'] = ['category_id' => 'categories'];
$zz['subselect']['sql_ignore'] = ['category_id'];

$zz['subselect']['concat_fields'] = ' ';
$zz['subselect']['concat_rows'] = ', ';
$zz['export_no_html'] = true;

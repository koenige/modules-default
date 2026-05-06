<?php

/**
 * default module
 * Table definition for 'webpages/blocks'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Blocks on Webpages';
$zz['table'] = '/*_PREFIX_*/webpages_blocks';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'webpage_block_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][5]['title'] = 'No.';
$zz['fields'][5]['field_name'] = 'sequence';
$zz['fields'][5]['type'] = 'number';
$zz['fields'][5]['auto_value'] = 'increment';
$zz['fields'][5]['def_val_ignore'] = true;
$zz['fields'][5]['exclude_from_search'] = true;

$zz['fields'][2]['title'] = 'Page';
$zz['fields'][2]['field_name'] = 'page_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT page_id, title, mother_page_id
	FROM /*_PREFIX_*/webpages
	ORDER BY sequence';
$zz['fields'][2]['display_field'] = 'webpage';
$zz['fields'][2]['search'] = '/*_PREFIX_*/webpages.title';
$zz['fields'][2]['show_hierarchy'] = 'mother_page_id';

$zz['fields'][3]['title'] = 'Block';
$zz['fields'][3]['field_name'] = 'block_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT block_id, title AS block_title, identifier
	FROM /*_PREFIX_*/blocks
	ORDER BY identifier';
$zz['fields'][3]['display_field'] = 'block_title';
$zz['fields'][3]['search'] = '/*_PREFIX_*/blocks.title';
$zz['fields'][3]['exclude_from_search'] = true;
$zz['fields'][3]['add_details'] = wrap_path('default_tables', 'blocks');

$zz['fields'][4]['title'] = 'Layout';
$zz['fields'][4]['field_name'] = 'layout_category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = 'SELECT category_id, category, main_category_id
	FROM /*_PREFIX_*/categories
	ORDER BY sequence, path';
$zz['fields'][4]['display_field'] = 'layout_category';
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['show_hierarchy_subtree'] = wrap_category_id('layout');
$zz['fields'][4]['default'] = wrap_category_id('layout/standard');

$zz['fields'][20]['title'] = 'Updated';
$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/webpages_blocks.*
	, /*_PREFIX_*/webpages.title AS webpage
	, /*_PREFIX_*/blocks.title AS block_title
	, layout_categories.category AS layout_category
	FROM /*_PREFIX_*/webpages_blocks
	LEFT JOIN /*_PREFIX_*/webpages USING (page_id)
	LEFT JOIN /*_PREFIX_*/blocks USING (block_id)
	LEFT JOIN /*_PREFIX_*/categories layout_categories
		ON /*_PREFIX_*/webpages_blocks.layout_category_id = layout_categories.category_id
';
$zz['sqlorder'] = ' ORDER BY /*_PREFIX_*/webpages.title, sequence';

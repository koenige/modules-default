<?php

/**
 * default module
 * Table definition for blocks
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Blocks';
$zz['table'] = '/*_PREFIX_*/blocks';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'block_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Title';
$zz['fields'][2]['field_name'] = 'title';
$zz['fields'][2]['type'] = 'text';

$zz['fields'][3]['title'] = 'Identifier';
$zz['fields'][3]['field_name'] = 'identifier';
$zz['fields'][3]['type'] = 'identifier';
$zz['fields'][3]['fields'][] = 'title';
$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][4]['title'] = 'Block Type';
$zz['fields'][4]['field_name'] = 'block_category_id';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['sql'] = "SELECT category_id, category, main_category_id
		, IF(
			/*_PREFIX_*/categories.parameters LIKE '%&default_blocks_detail_category=1%', 1, NULL
		) AS show_blocks_detail_category
	FROM /*_PREFIX_*/categories";
$zz['fields'][4]['sql_ignore'][] = 'show_blocks_detail_category';
$zz['fields'][4]['display_field'] = 'block_category';
$zz['fields'][4]['search'] = 'block_categories.category';
$zz['fields'][4]['show_hierarchy'] = 'main_category_id';
$zz['fields'][4]['show_hierarchy_subtree'] = wrap_category_id('blocks');
$zz['fields'][4]['default'] = wrap_category_id('blocks/general');
$zz['fields'][4]['dependent_fields'][5]['if_selected'] = 'show_blocks_detail_category';
$zz['fields'][4]['list_append_next'] = true;

$zz['fields'][5]['field_name'] = 'category_id';
$zz['fields'][5]['type'] = 'select';
$zz['fields'][5]['sql'] = 'SELECT category_id, category, main_category_id
	FROM /*_PREFIX_*/categories';
$zz['fields'][5]['display_field'] = 'subject_category';
$zz['fields'][4]['search'] = 'subject_categories.category';
$zz['fields'][5]['show_hierarchy'] = 'main_category_id';

$zz['fields'][6]['title'] = 'Block';
$zz['fields'][6]['field_name'] = 'block';
$zz['fields'][6]['type'] = 'memo';
$zz['fields'][6]['format'] = 'markdown';
$zz['fields'][6]['rows'] = 20;
$zz['fields'][6]['hide_in_list'] = true;

$zz['fields'][7] = zzform_include('blocks-media');
$zz['fields'][7]['title'] = 'Media';
$zz['fields'][7]['type'] = 'subtable';
$zz['fields'][7]['min_records'] = 0;
$zz['fields'][7]['max_records'] = 20;
$zz['fields'][7]['form_display'] = 'horizontal';
$zz['fields'][7]['sql'] .= ' ORDER BY sequence';
$zz['fields'][7]['fields'][2]['type'] = 'foreign_key';
$zz['fields'][7]['fields'][4]['type'] = 'sequence';
$zz['fields'][7]['class'] = 'hidden480';

$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;


$zz['sql'] = 'SELECT /*_PREFIX_*/blocks.*
		, block_categories.category AS block_category
		, subject_categories.category AS subject_category
	FROM /*_PREFIX_*/blocks
	LEFT JOIN /*_PREFIX_*/categories block_categories
		ON /*_PREFIX_*/blocks.block_category_id = block_categories.category_id
	LEFT JOIN /*_PREFIX_*/categories subject_categories
		ON /*_PREFIX_*/blocks.category_id = subject_categories.category_id
';
$zz['sqlorder'] = ' ORDER BY block_categories.sequence, subject_categories.category';

$zz['filter'][1]['title'] = wrap_text('Block Type');
$zz['filter'][1]['identifier'] = 'type';
$zz['filter'][1]['sql'] = 'SELECT /*_PREFIX_*/categories.category_id, category
	FROM /*_PREFIX_*/blocks
	LEFT JOIN /*_PREFIX_*/categories
		ON /*_PREFIX_*/blocks.block_category_id = /*_PREFIX_*/categories.category_id
	ORDER BY sequence, category';
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['field_name'] = 'block_category_id';
$zz['filter'][1]['where'] = 'block_category_id';

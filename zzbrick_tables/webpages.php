<?php 

/**
 * default module
 * Table definition for 'webpages'
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2007-2009, 2016, 2018-2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Webpages';
$zz['table'] = '/*_PREFIX_*/webpages';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'page_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'title';
$zz['fields'][2]['list_prefix'] = '<strong>';
$zz['fields'][2]['list_suffix'] = '</strong>';
$zz['fields'][2]['list_append_next'] = true;
$zz['fields'][2]['if'][1]['list_prefix'] = '<del>';
$zz['fields'][2]['if'][1]['list_suffix'] = '</del>';

$zz['fields'][3]['field_name'] = 'content';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['rows'] = 20;
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][4]['field_name'] = 'identifier';
$zz['fields'][4]['explanation'] = 'Address of page, should show hierarchy, no / at the end!<br>The identifier should contain only letters a-z, numbers 0-9 and the - sign.';
$zz['fields'][4]['list_prefix'] = '<p><small>';
$zz['fields'][4]['list_suffix'] = '</small></p>';
$zz['fields'][4]['append_next'] = true;

$zz['fields'][5]['field_name'] = 'ending';
$zz['fields'][5]['type'] = 'select';
$zz['fields'][5]['enum'] = ['/', '.html', 'none'];
$zz['fields'][5]['default'] = '/';
$zz['fields'][5]['hide_in_list'] = true;

$zz['fields'][6]['title_tab'] = 'Seq.';
$zz['fields'][6]['field_name'] = 'sequence';
$zz['fields'][6]['type'] = 'number';
$zz['fields'][6]['null'] = true;
$zz['fields'][6]['class'] = 'hidden480';

$zz['fields'][7]['title'] = 'Subpage&nbsp;of';
$zz['fields'][7]['field_name'] = 'mother_page_id';
$zz['fields'][7]['type'] = 'select';
$zz['fields'][7]['sql'] = 'SELECT page_id, title, mother_page_id, identifier
	FROM /*_PREFIX_*/webpages
	ORDER BY sequence';
$zz['fields'][7]['show_hierarchy'] = 'mother_page_id';
$zz['fields'][7]['show_hierarchy_same_table'] = true;
$zz['fields'][7]['display_field'] = 'mother_title';
$zz['fields'][7]['exclude_from_search'] = true;
$zz['fields'][7]['hide_in_list'] = true;

$zz['fields'][8] = false; // put media subtable here if needed

$zz['fields'][9]['title_tab'] = 'WWW?';
$zz['fields'][9]['title'] = 'Published?';
$zz['fields'][9]['field_name'] = 'live';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = ['yes', 'no'];
$zz['fields'][9]['default'] = 'yes';
$zz['fields'][9]['hide_in_list'] = true;

$zz['fields'][10]['field_name'] = 'menu';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['show_values_as_list'] = true;
$zz['fields'][10]['hide_novalue'] = false;
$zz['fields'][10]['enum'] = ['top', 'bottom', 'internal'];
$zz['fields'][10]['class'] = 'hidden320';
$zz['fields'][10]['hide_in_list_if_empty'] = true;

$zz['fields'][15] = false; // parameters

$zz['fields'][99]['title'] = 'Last Update';
$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/webpages.* 
	, motherpages.title AS mother_title
	FROM /*_PREFIX_*/webpages
	LEFT JOIN /*_PREFIX_*/webpages AS motherpages 
		ON /*_PREFIX_*/webpages.mother_page_id = motherpages.page_id';
$zz['sqlorder'] = ' ORDER BY sequence, identifier';

$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][7]['field_name'];
$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];

$zz['set_redirect'][] = ['old' => '%s', 'new' => '%s', 'field_name' => 'identifier'];

$zz['conditions'][1]['scope'] = 'record';
$zz['conditions'][1]['where'] = '/*_PREFIX_*/webpages.live = "no"';

$zz_conf['copy'] = true;

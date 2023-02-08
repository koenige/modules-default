<?php 

/**
 * default module
 * Table definition for 'webpages'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2007-2009, 2016, 2018-2023 Gustaf Mossakowski
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
$zz['fields'][2]['typo_cleanup'] = true;
$zz['fields'][2]['typo_remove_double_spaces'] = true;

$zz['fields'][3]['field_name'] = 'content';
$zz['fields'][3]['type'] = 'memo';
$zz['fields'][3]['rows'] = 20;
$zz['fields'][3]['format'] = 'markdown';
$zz['fields'][3]['hide_in_list'] = true;

$zz['fields'][11]['field_name'] = 'description';
$zz['fields'][11]['type'] = 'memo';
$zz['fields'][11]['rows'] = 2;
$zz['fields'][11]['hide_in_list'] = true;
$zz['fields'][11]['explanation'] = 'Short text (max. 160 characters), only appears in search results.';

$zz['fields'][4]['field_name'] = 'identifier';
$zz['fields'][4]['type'] = 'url+placeholder';
$zz['fields'][4]['explanation'] = 'Address of page, should show hierarchy, no / at the end!<br>The identifier should contain only letters a-z, numbers 0-9 and the - sign.';
$zz['fields'][4]['list_prefix'] = '<p><small>';
$zz['fields'][4]['list_suffix'] = '</small></p>';
$zz['fields'][4]['append_next'] = true;
$zz['fields'][4]['link'] = [
	'field' => 'webpage_url'
];

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
$zz['fields'][7]['character_set'] = 'utf8';

if (wrap_get_setting('default_webpages_media')) {
	$zz['fields'][8] = zzform_include_table('webpages-media');
	$zz['fields'][8]['title'] = 'Media';
	$zz['fields'][8]['type'] = 'subtable';
	$zz['fields'][8]['min_records'] = 1;
	$zz['fields'][8]['max_records'] = 20;
	$zz['fields'][8]['form_display'] = 'horizontal';
	$zz['fields'][8]['sql'] .= ' ORDER BY /*_PREFIX_*/webpages.title DESC, sequence';
	$zz['fields'][8]['fields'][2]['type'] = 'foreign_key';
	$zz['fields'][8]['fields'][4]['type'] = 'sequence';
	$zz['fields'][8]['class'] = 'hidden480';
}

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

$zz['fields'][15]['field_name'] = 'parameters';
$zz['fields'][15]['type'] = 'parameter';
$zz['fields'][15]['hide_in_list'] = true;

$zz['fields'][16] = []; // website

$zz['fields'][99]['title'] = 'Last Update';
$zz['fields'][99]['field_name'] = 'last_update';
$zz['fields'][99]['type'] = 'timestamp';
$zz['fields'][99]['hide_in_list'] = true;

$zz['sql'] = 'SELECT /*_PREFIX_*/webpages.* 
		, main_pages.title AS mother_title
		, IF(/*_PREFIX_*/webpages.live = "yes", IF(LOCATE("*", /*_PREFIX_*/webpages.identifier), NULL,
				IF(LOCATE("%", /*_PREFIX_*/webpages.identifier), NULL, 
			CONCAT(/*_PREFIX_*/webpages.identifier, IF(STRCMP(/*_PREFIX_*/webpages.ending, "none"), /*_PREFIX_*/webpages.ending, "")))
		), NULL) AS webpage_url
	FROM /*_PREFIX_*/webpages
	LEFT JOIN /*_PREFIX_*/webpages AS main_pages 
		ON /*_PREFIX_*/webpages.mother_page_id = main_pages.page_id';
$zz['sqlorder'] = ' ORDER BY sequence, identifier';

$zz['list']['hierarchy']['mother_id_field_name'] = $zz['fields'][7]['field_name'];
$zz['list']['hierarchy']['display_in'] = $zz['fields'][2]['field_name'];

$zz['set_redirect'][] = ['old' => '%s', 'new' => '%s', 'field_name' => 'identifier'];

$zz['conditions'][1]['scope'] = 'record';
$zz['conditions'][1]['where'] = '/*_PREFIX_*/webpages.live = "no"';

if (!empty($brick['data']['website_id']))
	$zz['where']['website_id'] = $brick['data']['website_id'];

$zz_conf['copy'] = true;

if (!empty($zz_setting['multiple_websites'])) {
	if (!empty($_GET['where']['website_id'])) $website = $_GET['where']['website_id'];
	elseif (!empty($_GET['filter']['website'])) $website = $_GET['filter']['website'];
	elseif (!empty($zz['where']['website_id'])) $website = $zz['where']['website_id'];
	else $website = false;

	$zz['fields'][16]['field_name'] = 'website_id';
	$zz['fields'][16]['type'] = 'write_once';
	$zz['fields'][16]['type_detail'] = 'select';
	$zz['fields'][16]['sql'] = 'SELECT website_id, domain
		FROM /*_PREFIX_*/websites
		ORDER BY domain';
	if (!empty($zz_setting['website_id_default']))
		$zz['fields'][16]['default'] = $zz_setting['website_id_default'];
	$zz['fields'][16]['display_field'] = 'domain';
	$zz['fields'][16]['exclude_from_search'] = true;
	$zz['fields'][16]['if']['where']['hide_in_list'] = true;
	if (!empty($_GET['filter']['website'])) {
		$zz['fields'][16]['hide_in_list'] = true;
		$zz['fields'][16]['hide_in_form'] = true;
		$zz['fields'][16]['type'] = 'hidden';
		$zz['fields'][16]['value'] = $_GET['filter']['website'];
	}

	$zz['fields'][7]['sql'] = sprintf('SELECT page_id, title, mother_page_id, domain
			, /*_PREFIX_*/webpages.identifier
		FROM /*_PREFIX_*/webpages
		LEFT JOIN /*_PREFIX_*/websites USING (website_id)
		%s
		ORDER BY sequence'
		, $website ? sprintf(' WHERE website_id = %d', $website) : ''
	);

	$zz['sql'] = 'SELECT /*_PREFIX_*/webpages.*
			, main_pages.title AS mother_title
			, IF(/*_PREFIX_*/webpages.live = "yes", IF(LOCATE("*", /*_PREFIX_*/webpages.identifier), NULL,
					IF(LOCATE("%", /*_PREFIX_*/webpages.identifier), NULL, 
				CONCAT(IF(SUBSTRING(/*_PREFIX_*/webpages.identifier, 1, 1) = "/", 
					CONCAT("https://", IFNULL(/*_PREFIX_*/_settings.setting_value, domain)), ""
				), /*_PREFIX_*/webpages.identifier, IF(STRCMP(/*_PREFIX_*/webpages.ending, "none"), /*_PREFIX_*/webpages.ending, "")))
			), NULL) AS webpage_url
			, /*_PREFIX_*/websites.domain
		FROM /*_PREFIX_*/webpages
		LEFT JOIN /*_PREFIX_*/websites USING (website_id) 
		LEFT JOIN /*_PREFIX_*/webpages AS main_pages 
			ON /*_PREFIX_*/webpages.mother_page_id = main_pages.page_id
		LEFT JOIN /*_PREFIX_*/_settings
			ON /*_PREFIX_*/_settings.website_id = /*_PREFIX_*/websites.website_id
			AND setting_key = "canonical_hostname"
		';

	if (empty($zz['where']['website_id']) AND empty($_GET['where']['website_id'])) {
		$zz['filter'][1]['sql'] = 'SELECT website_id, domain
			FROM /*_PREFIX_*/websites
			WHERE website_id != 1
			ORDER BY domain';
		$zz['filter'][1]['title'] = 'Website';
		$zz['filter'][1]['identifier'] = 'website';
		$zz['filter'][1]['type'] = 'list';
		$zz['filter'][1]['field_name'] = 'website_id';
		$zz['filter'][1]['where'] = '/*_PREFIX_*/webpages.website_id';
	}

	$zz['subtitle']['website_id']['sql'] = $zz['fields'][16]['sql'];
	$zz['subtitle']['website_id']['var'] = ['domain'];

	unset($zz['set_redirect']);
	$zz['set_redirect'][] = [
		'old' => '%s', 'new' => '%s', 'field_name' => 'identifier', 'website_id' => 'website_id'
	];
}

if (!wrap_access('default_webpages_admin')) {
	unset($zz['fields'][15]); // no parameters
	$zz['sql'] = wrap_edit_sql($zz['sql'], 'WHERE', '(ISNULL(webpages.parameters) OR !LOCATE("edit=admin", webpages.parameters))
		AND (ISNULL(main_pages.parameters) OR !LOCATE("edit=admin", main_pages.parameters))');
}

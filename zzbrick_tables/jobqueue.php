<?php 

/**
 * default module
 * table script: background job queue
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2015, 2019-2021, 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Background Job Queue';
$zz['table'] = '_jobqueue';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'job_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Category';
$zz['fields'][2]['field_name'] = 'job_category_id';
$zz['fields'][2]['type'] = 'write_once';
$zz['fields'][2]['type_detail'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT category_id, category, main_category_id
	FROM categories';
$zz['fields'][2]['display_field'] = 'category';
$zz['fields'][2]['show_hierarchy'] = 'main_category_id';
$zz['fields'][2]['show_hierarchy_subtree'] = wrap_category_id('jobs');
$zz['fields'][2]['default'] = wrap_category_id('jobs/background');
$zz['fields'][2]['key_field_name'] = 'category_id';
$zz['fields'][2]['list_append_next'] = true;

$zz['fields'][14]['field_name'] = 'website_id';
if (wrap_setting('multiple_websites')) {
	$zz['fields'][14]['type'] = 'write_once';
	$zz['fields'][14]['type_detail'] = 'select';
	$zz['fields'][14]['sql'] = 'SELECT website_id, domain
		FROM /*_PREFIX_*/websites
		ORDER BY domain';
	$zz['fields'][14]['default'] = wrap_setting('website_id_default') ?? 1;
	$zz['fields'][14]['display_field'] = 'domain';
	$zz['fields'][14]['exclude_from_search'] = true;
	$zz['fields'][14]['if']['where']['hide_in_list'] = true;
	$zz['fields'][14]['list_append_next'] = true;
	$zz['fields'][14]['list_append_show_title'] = true;
	$zz['fields'][14]['list_prefix'] = '<br>';
	if (!empty($_GET['filter']['website']))
		$zz['fields'][14]['hide_in_list'] = true;
} else {
	$zz['fields'][14]['hide_in_list'] = true;
	$zz['fields'][14]['hide_in_form'] = true;
	$zz['fields'][14]['type'] = 'hidden';
}

$zz['fields'][3]['title'] = 'Job URL';
$zz['fields'][3]['list_append_show_title'] = true;
$zz['fields'][3]['field_name'] = 'job_url';
$zz['fields'][3]['type'] = 'text';
$zz['fields'][3]['list_prefix'] = '<br>';
$zz['fields'][3]['link'] = [
	'field' => 'webpage_url'
];

$zz['fields'][13]['field_name'] = 'username';
$zz['fields'][13]['hide_in_list_if_empty'] = true;

$zz['fields'][4]['title_tab'] = 'P.';
$zz['fields'][4]['field_name'] = 'priority';
$zz['fields'][4]['type'] = 'number';
$zz['fields'][4]['null'] = true;

$zz['fields'][5]['field_name'] = 'created';
$zz['fields'][5]['type'] = 'write_once';
$zz['fields'][5]['type_detail'] = 'datetime';
$zz['fields'][5]['default'] = date('Y-m-d H:i:s');
$zz['fields'][5]['list_append_next'] = true;

$zz['fields'][8]['field_name'] = 'wait_until';
$zz['fields'][8]['type'] = 'datetime';
$zz['fields'][8]['list_append_show_title'] = true;
$zz['fields'][8]['list_prefix'] = '<br>';

$zz['fields'][6]['field_name'] = 'started';
$zz['fields'][6]['type'] = 'datetime';
$zz['fields'][6]['list_append_next'] = true;

$zz['fields'][7]['field_name'] = 'finished';
$zz['fields'][7]['type'] = 'datetime';
$zz['fields'][7]['list_prefix'] = '<br>';
$zz['fields'][7]['list_append_show_title'] = true;

$zz['fields'][9]['title'] = 'Job Status';
$zz['fields'][9]['field_name'] = 'job_status';
$zz['fields'][9]['type'] = 'select';
$zz['fields'][9]['enum'] = ['not_started', 'running', 'successful', 'failed', 'abandoned'];
$zz['fields'][9]['enum_title'] = [
	wrap_text('not started'), wrap_text('running'), wrap_text('successful'),
	wrap_text('failed'), wrap_text('abandoned')
];
$zz['fields'][9]['default'] = 'not_started';

$zz['fields'][10]['title'] = 'Try No.';
$zz['fields'][10]['title_tab'] = 'No.';
$zz['fields'][10]['field_name'] = 'try_no';
$zz['fields'][10]['type'] = 'number';
$zz['fields'][10]['null'] = true;
$zz['fields'][10]['default'] = 0;

$zz['fields'][11]['title'] = 'Error Message';
$zz['fields'][11]['field_name'] = 'error_msg';
$zz['fields'][11]['type'] = 'memo';
$zz['fields'][11]['hide_in_list'] = true;

$zz['fields'][12]['title'] = 'Category No.';
$zz['fields'][12]['field_name'] = 'job_category_no';
$zz['fields'][12]['type'] = 'number';
$zz['fields'][12]['hide_in_list'] = true;
$zz['fields'][12]['default'] = 1;

$zz['fields'][15]['field_name'] = 'lock_hash';
$zz['fields'][15]['type'] = 'hidden';
$zz['fields'][15]['hide_in_list'] = true;


$zz['sql'] = 'SELECT _jobqueue.*
		, categories.category
		, websites.domain
		, CONCAT(IF(SUBSTRING(/*_PREFIX_*/_jobqueue.job_url, 1, 1) = "/" AND domain != "*", 
				CONCAT("https://", IFNULL(/*_PREFIX_*/_settings.setting_value, domain)), ""
			), /*_PREFIX_*/_jobqueue.job_url
		) AS webpage_url
	FROM _jobqueue
	LEFT JOIN categories
		ON categories.category_id = _jobqueue.job_category_id
	LEFT JOIN websites USING (website_id)
	LEFT JOIN /*_PREFIX_*/_settings
		ON /*_PREFIX_*/_settings.website_id = /*_PREFIX_*/websites.website_id
		AND setting_key = "canonical_hostname"
';
$zz['sqlorder'] = ' ORDER BY domain, category, IF(ISNULL(_jobqueue.started), 0, 1), IF(ISNULL(_jobqueue.finished), 0, 1), _jobqueue.started DESC, _jobqueue.finished DESC, priority ASC, job_id';

if (wrap_setting('multiple_websites')) {
	$zz['filter'][2]['sql'] = 'SELECT website_id, domain
		FROM /*_PREFIX_*/websites
		JOIN _jobqueue USING (website_id)
		WHERE website_id != 1
		ORDER BY domain';
	$zz['filter'][2]['title'] = 'Website';
	$zz['filter'][2]['identifier'] = 'website';
	$zz['filter'][2]['type'] = 'list';
	$zz['filter'][2]['field_name'] = 'website_id';
	$zz['filter'][2]['where'] = '/*_PREFIX_*/_jobqueue.website_id';
}

$zz['filter'][1]['title'] = wrap_text('Category');
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'job_category_id';
$zz['filter'][1]['sql'] = 'SELECT DISTINCT category_id, category
	FROM categories
	JOIN _jobqueue
		ON categories.category_id = _jobqueue.job_category_id
	ORDER BY category';

$zz['filter'][3]['title'] = wrap_text('Job Status');
$zz['filter'][3]['identifier'] = 'status';
$zz['filter'][3]['where'] = 'job_status';
$zz['filter'][3]['type'] = 'list';
$zz['filter'][3]['selection'] = array_combine($zz['fields'][9]['enum'], $zz['fields'][9]['enum_title']);

wrap_setting('zzform_logging', false);

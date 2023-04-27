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
$zz['fields'][2]['key_field_name'] = 'category_id';

$zz['fields'][3]['title'] = 'Job URL';
$zz['fields'][3]['field_name'] = 'job_url';
$zz['fields'][3]['type'] = 'url';

$zz['fields'][13]['field_name'] = 'username';
$zz['fields'][13]['hide_in_list_if_empty'] = true;

$zz['fields'][4]['field_name'] = 'priority';
$zz['fields'][4]['type'] = 'number';
$zz['fields'][4]['null'] = true;

$zz['fields'][5]['field_name'] = 'created';
$zz['fields'][5]['type'] = 'write_once';
$zz['fields'][5]['type_detail'] = 'datetime';
$zz['fields'][5]['default'] = date('Y-m-d H:i:s');

$zz['fields'][6]['field_name'] = 'started';
$zz['fields'][6]['type'] = 'datetime';

$zz['fields'][7]['field_name'] = 'finished';
$zz['fields'][7]['type'] = 'datetime';

$zz['fields'][8]['field_name'] = 'wait_until';
$zz['fields'][8]['type'] = 'datetime';

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


$zz['sql'] = 'SELECT _jobqueue.*, categories.category
	FROM _jobqueue
	LEFT JOIN categories
		ON categories.category_id = _jobqueue.job_category_id
';
$zz['sqlorder'] = ' ORDER BY category, IF(ISNULL(_jobqueue.started), 0, 1), IF(ISNULL(_jobqueue.finished), 0, 1), _jobqueue.started DESC, _jobqueue.finished DESC, priority ASC, job_id';

$zz['filter'][1]['title'] = wrap_text('Category');
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'job_category_id';
$zz['filter'][1]['sql'] = 'SELECT DISTINCT category_id, category
	FROM categories
	JOIN _jobqueue
		ON categories.category_id = _jobqueue.job_category_id
	ORDER BY category';

wrap_setting('zzform_logging', false);

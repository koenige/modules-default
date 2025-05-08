<?php 

/**
 * default module
 * Table definition for '_cronjobs'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2013-2015, 2018, 2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Cron Jobs';
$zz['table'] = '/*_PREFIX_*/_cronjobs';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'cronjob_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Cron Job';
$zz['fields'][2]['field_name'] = 'cronjob';

$zz['fields'][3]['title'] = 'URL';
$zz['fields'][3]['field_name'] = 'url';
$zz['fields'][3]['type'] = 'url';

$zz['fields'][4]['field_name'] = 'active';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['enum'] = ['yes', 'no'];
$zz['fields'][4]['default'] = 'yes';

$zz['fields'][5]['title'] = 'Minute';
$zz['fields'][5]['title_tab'] = 'Min.';
$zz['fields'][5]['field_name'] = 'job_minute';
$zz['fields'][5]['type'] = 'text';
$zz['fields'][5]['null'] = true;
$zz['fields'][5]['display_field'] = 'display_minute';
$zz['fields'][5]['replace_values'] = ['*' => ''];

$zz['fields'][6]['title'] = 'Hour';
$zz['fields'][6]['title_tab'] = 'Hr.';
$zz['fields'][6]['field_name'] = 'job_hour';
$zz['fields'][6]['type'] = 'text';
$zz['fields'][6]['null'] = true;
$zz['fields'][6]['display_field'] = 'display_hour';
$zz['fields'][6]['replace_values'] = ['*' => ''];

$zz['fields'][7]['title'] = 'Day';
$zz['fields'][7]['field_name'] = 'job_day';
$zz['fields'][7]['type'] = 'text';
$zz['fields'][7]['display_field'] = 'display_day';
$zz['fields'][7]['replace_values'] = ['*' => ''];

$zz['fields'][8]['title'] = 'Month';
$zz['fields'][8]['title_tab'] = 'Mth.';
$zz['fields'][8]['field_name'] = 'job_month';
$zz['fields'][8]['type'] = 'text';
$zz['fields'][8]['display_field'] = 'display_month';
$zz['fields'][8]['replace_values'] = ['*' => ''];

$zz['fields'][9]['title'] = 'Weekday';
$zz['fields'][9]['title_tab'] = 'Wd.';
$zz['fields'][9]['field_name'] = 'job_weekday';
$zz['fields'][9]['type'] = 'text';
$zz['fields'][9]['display_field'] = 'display_weekday';
$zz['fields'][9]['null'] = true;
$zz['fields'][9]['replace_values'] = ['*' => ''];

$zz['fields'][10]['title_tab'] = 'Seq.';
$zz['fields'][10]['field_name'] = 'sequence';
$zz['fields'][10]['type'] = 'number';


$zz['sql'] = 'SELECT /*_PREFIX_*/_cronjobs.*
		, IFNULL(job_minute, "*") AS display_minute
		, IFNULL(job_hour, "*") AS display_hour
		, IFNULL(job_day, "*") AS display_day
		, IFNULL(job_month, "*") AS display_month
		, IFNULL(job_weekday, "*") AS display_weekday
	FROM /*_PREFIX_*/_cronjobs
';
$zz['sqlorder'] = ' ORDER BY IF(ISNULL(sequence), 1, 0), sequence, cronjob, url';

$zz['explanation'] = '<p>'.wrap_text(
	'Here, you can add cron jobs. Please note: the execution of the cron jobs depends on the real cron job which has to be triggered according to these dates. It is possible to enter one or more numeric values per field, separated by a comma.'
).'</p>';

$zz['record']['copy'] = true;

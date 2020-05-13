<?php 

/**
 * default module
 * Database table for settings (website settings, user settings)
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2008-2014, 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Website settings';
$zz['table'] = '/*_PREFIX_*/_settings';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'setting_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][3]['title'] = 'Key';
$zz['fields'][3]['type'] = 'write_once';
$zz['fields'][3]['field_name'] = 'setting_key';
$zz['fields'][3]['list_append_next'] = true;
$zz['fields'][3]['class'] = 'block480a';

$zz['fields'][6]['field_name'] = 'explanation';
$zz['fields'][6]['type'] = 'memo';
$zz['fields'][6]['rows'] = 3;
$zz['fields'][6]['list_prefix'] = '<p class="explanation" style="margin: .75em 2.5em; max-width: 40em; "><em>';
$zz['fields'][6]['list_suffix'] = '</em></p>';

$zz['fields'][4]['title'] = 'Value';
$zz['fields'][4]['field_name'] = 'setting_value';
$zz['fields'][4]['null'] = true;
$zz['fields'][4]['null_string'] = true;
$zz['fields'][4]['list_append_next'] = true;
$zz['fields'][4]['class'] = 'block480 hyphenate';

$zz['sql'] = 'SELECT /*_PREFIX_*/_settings.*
	FROM /*_PREFIX_*/_settings
';
$zz['sqlorder'] = ' ORDER BY setting_key, setting_value';

$zz_conf['copy'] = true;

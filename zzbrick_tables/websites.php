<?php 

/**
 * default module
 * Table definition for 'websites'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016-2017, 2019-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Websites';
$zz['table'] = '/*_PREFIX_*/websites';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'website_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['field_name'] = 'website';
$zz['fields'][2]['typo_remove_double_spaces'] = true;

$zz['fields'][3]['field_name'] = 'domain';
$zz['fields'][3]['explanation'] = 'Domain name without subdomain www';

$zz['sql'] = 'SELECT websites.*
	FROM websites
';
$zz['sqlorder'] = 'ORDER BY domain';

$zz['details'][1]['title'] = 'Webpages';
$zz['details'][1]['link'] = [
	'string1' => 'webpages?filter[website]=', 
	'field1' => 'website_id'
];

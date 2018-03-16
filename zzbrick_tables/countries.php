<?php 

/**
 * default module
 * Table definition for 'countries'
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2012, 2018 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Countries';
$zz['table'] = '/*_PREFIX_*/countries';

$zz['fields'][1]['field_name'] = 'country_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Country Code';
$zz['fields'][2]['field_name'] = 'country_code';
$zz['fields'][2]['explanation'] = 'Country code according to ISO 3166';

$zz['fields'][3]['title'] = 'Country';
$zz['fields'][3]['field_name'] = 'country';

$zz['fields'][10]['field_name'] = 'website';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = array('yes', 'no');
$zz['fields'][10]['default'] = 'no';
$zz['fields'][10]['explanation'] = 'Will country be used on website?';

$zz['sql'] = 'SELECT /*_PREFIX_*/countries.*
	FROM /*_PREFIX_*/countries';
$zz['sqlorder'] = ' ORDER BY country_code';
$zz['sql_translate'] = ['country_id' => 'countries'];

$zz['filter'][1]['title'] = 'Web';
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'IF(STRCMP(website, "yes"), 2, 1)';
$zz['filter'][1]['selection'][1] = wrap_text('yes');
$zz['filter'][1]['selection'][2] = wrap_text('no');

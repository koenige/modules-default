<?php 

/**
 * default module
 * Table definition for 'languages' according to ISO 639-1 and -2
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2012 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Languages';
$zz['table'] = '/*_PREFIX_*/languages';

$zz['fields'][1]['field_name'] = 'language_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'ISO 639-2 T';
$zz['fields'][2]['title_tab'] = '639-2 T';
$zz['fields'][2]['field_name'] = 'iso_639_2t';
$zz['fields'][2]['explanation'] = 'Language code ISO 639-2, Terminology';

$zz['fields'][3]['title'] = 'ISO 639-2 B';
$zz['fields'][3]['title_tab'] = '639-2 B';
$zz['fields'][3]['field_name'] = 'iso_639_2b';
$zz['fields'][3]['explanation'] = 'Language code ISO 639-2, Bibliographic';

$zz['fields'][4]['title'] = 'ISO 639-1';
$zz['fields'][4]['title_tab'] = '639-1';
$zz['fields'][4]['field_name'] = 'iso_639_1';
$zz['fields'][4]['explanation'] = 'Language code ISO 639-1';

$zz['fields'][6]['title'] = 'Language, english';
$zz['fields'][6]['field_name'] = 'language_en';

$zz['fields'][5]['title'] = 'Language, german';
$zz['fields'][5]['field_name'] = 'language_de';
$zz['fields'][5]['hide_in_list'] = true;

$zz['fields'][7]['title'] = 'Language, french';
$zz['fields'][7]['field_name'] = 'language_fr';
$zz['fields'][7]['hide_in_list'] = true;

$zz['fields'][10]['field_name'] = 'website';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = array('yes', 'no');
$zz['fields'][10]['default'] = 'no';
$zz['fields'][10]['explanation'] = 'Will language be used on website?';

$zz['sql'] = 'SELECT /*_PREFIX_*/languages.*
	FROM /*_PREFIX_*/languages';
$zz['sqlorder'] = ' ORDER BY iso_639_2t';

$zz['filter'][1]['title'] = 'Web';
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'IF(STRCMP(website, "yes"), 2, 1)';
$zz['filter'][1]['selection'][1] = wrap_text('yes');
$zz['filter'][1]['selection'][2] = wrap_text('no');

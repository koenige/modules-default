<?php 

/**
 * default module
 * Table definition for 'languages' according to ISO 639-1 and -2
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2012, 2018-2019, 2021-2022, 2025 Gustaf Mossakowski
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

$zz['fields'][5]['title'] = 'Language';
$zz['fields'][5]['field_name'] = 'language';
$zz['fields'][5]['typo_remove_double_spaces'] = true;

$zz['fields'][11]['field_name'] = 'variation';
$zz['fields'][11]['explanation'] = 'Variation of language, e. g. informal';

$zz['fields'][10]['field_name'] = 'website';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = ['yes', 'no'];
$zz['fields'][10]['default'] = 'no';
$zz['fields'][10]['explanation'] = 'Will language be used on website?';

$zz['sql'] = 'SELECT /*_PREFIX_*/languages.*
	FROM /*_PREFIX_*/languages';
$zz['sqlorder'] = ' ORDER BY iso_639_2t';

$zz['filter'][1]['title'] = wrap_text('Web');
$zz['filter'][1]['identifier'] = 'web';
$zz['filter'][1]['type'] = 'list';
$zz['filter'][1]['where'] = 'IF(STRCMP(website, "yes"), 2, 1)';
$zz['filter'][1]['selection'][1] = wrap_text('yes');
$zz['filter'][1]['selection'][2] = wrap_text('no');

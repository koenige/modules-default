<?php 

/**
 * default module
 * Database table for redirects of URLs
 * DB-Tabelle zur Eingabe von Umleitungen von URLs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2006-2016, 2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


// access restriction has to be set in the file including this file
// Bitte Zugriffsbeschränkungen in der Datei, die diese einbindet, definieren!

$zz['title'] = 'Redirects';
$zz['explanation'] = '%%% text Information about redirects %%%';
$zz['table'] = '/*_PREFIX_*/redirects';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'redirect_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Old URL';
$zz['fields'][2]['field_name'] = 'old_url';
$zz['fields'][2]['type'] = 'text';
$zz['fields'][2]['class'] = 'block480a';

$zz['fields'][3]['title'] = 'New URL';
$zz['fields'][3]['field_name'] = 'new_url';
$zz['fields'][3]['type'] = 'text';
$zz['fields'][3]['class'] = 'block480';

$zz['fields'][4]['field_name'] = 'code';
$zz['fields'][4]['type'] = 'select';
$zz['fields'][4]['default'] = 301;
$zz['fields'][4]['enum'] = [301, 303, 307, 403, 410];
$zz['fields'][4]['enum_abbr'] = [
	wrap_text('Moved Permanently'), 
	wrap_text('See Other'), wrap_text('Temporary Redirect'), 
	wrap_text('Forbidden'), wrap_text('Gone')
];
$zz['fields'][4]['group_in_list'] = true;

$zz['fields'][5]['field_name'] = 'area';
$zz['fields'][5]['group_in_list'] = true;

if (!empty($zz_setting['multiple_websites'])) {
	$zz['fields'][6]['field_name'] = 'website_id';
	$zz['fields'][6]['type'] = 'select';
	$zz['fields'][6]['sql'] = 'SELECT website_id, domain
		FROM /*_PREFIX_*/websites
		ORDER BY domain';
	if (!empty($zz_setting['website_id_default']))
		$zz['fields'][6]['default'] = $zz_setting['website_id_default'];
	$zz['fields'][6]['display_field'] = 'domain';
}

$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['sql'] = 'SELECT * 
	FROM /*_PREFIX_*/redirects';
$zz['sqlorder'] = ' ORDER BY old_url, new_url';

if (!empty($zz_setting['multiple_websites'])) {
	$zz['sql'] = 'SELECT /*_PREFIX_*/redirects.*
			, /*_PREFIX_*/websites.domain
		FROM /*_PREFIX_*/redirects
		LEFT JOIN /*_PREFIX_*/websites USING (website_id)';

	$zz['filter'][1]['sql'] = 'SELECT website_id, domain
		FROM /*_PREFIX_*/websites
		ORDER BY domain';
	$zz['filter'][1]['title'] = 'Website';
	$zz['filter'][1]['identifier'] = 'website';
	$zz['filter'][1]['type'] = 'list';
	$zz['filter'][1]['field_name'] = 'website_id';
	$zz['filter'][1]['where'] = '/*_PREFIX_*/redirects.website_id';
}

$zz_conf['copy'] = true;
$zz_conf['add'] = true; // @todo remove this later, since defined globally, may be disabled

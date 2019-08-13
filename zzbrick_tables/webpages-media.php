<?php 

/**
 * default module
 * Table definition for 'webpages/media'
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2017-2019 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz_sub['title'] = 'Webpages/Media';
$zz_sub['table'] = '/*_PREFIX_*/webpages_media';

$zz_sub['fields'][1]['title'] = 'ID';
$zz_sub['fields'][1]['field_name'] = 'page_medium_id';
$zz_sub['fields'][1]['type'] = 'id';

$zz_sub['fields'][2]['title'] = 'Page';
$zz_sub['fields'][2]['field_name'] = 'page_id';
$zz_sub['fields'][2]['type'] = 'select';
$zz_sub['fields'][2]['sql'] = 'SELECT page_id, title, mother_page_id
	FROM /*_PREFIX_*/webpages
	ORDER BY sequence';
$zz_sub['fields'][2]['display_field'] = 'webpage';
$zz_sub['fields'][2]['show_hierarchy'] = 'mother_page_id';

$zz_sub['fields'][4]['title'] = 'No.';
$zz_sub['fields'][4]['field_name'] = 'sequence';
$zz_sub['fields'][4]['type'] = 'number';
$zz_sub['fields'][4]['auto_value'] = 'increment';
$zz_sub['fields'][4]['def_val_ignore'] = true;

$zz_sub['fields'][5]['title'] = 'Preview';
$zz_sub['fields'][5]['field_name'] = 'image';
$zz_sub['fields'][5]['type'] = 'image';
$zz_sub['fields'][5]['class'] = 'preview';
$zz_sub['fields'][5]['path'] = [
	'root' => $zz_setting['media_folder'], 
	'webroot' => $zz_setting['files_path'],
	'string1' => '/',
	'field1' => 'filename',
	'string2' => '.',
	'string3' => $zz_setting['media_preview_size'],
	'string4' => '.',
	'extension' => 'thumb_extension',
	'webstring1' => '?v=',
	'webfield1' => 'version'
];

$zz_sub['fields'][3]['title'] = 'Medium';
$zz_sub['fields'][3]['field_name'] = 'medium_id';
$zz_sub['fields'][3]['type'] = 'select';
$zz_sub['fields'][3]['sql'] = sprintf('SELECT /*_PREFIX_*/media.medium_id, folders.title AS folder
		, CONCAT("[", /*_PREFIX_*/media.medium_id, "] ", /*_PREFIX_*/media.title) AS image
	FROM /*_PREFIX_*/media 
	LEFT JOIN /*_PREFIX_*/media folders
		ON /*_PREFIX_*/media.main_medium_id = folders.medium_id
	WHERE /*_PREFIX_*/media.filetype_id != %d
	ORDER BY folders.title, /*_PREFIX_*/media.title', $zz_setting['filetype_ids']['folder']);
$zz_sub['fields'][3]['sql_character_set'] = ['utf8', 'utf8', 'utf8'];
$zz_sub['fields'][3]['id_field_name'] = '/*_PREFIX_*/media.medium_id';
$zz_sub['fields'][3]['display_field'] = 'image';
$zz_sub['fields'][3]['group'] = 'folder';
$zz_sub['fields'][3]['exclude_from_search'] = true;

$zz_sub['fields'][20]['title'] = 'Updated';
$zz_sub['fields'][20]['field_name'] = 'last_update';
$zz_sub['fields'][20]['type'] = 'timestamp';
$zz_sub['fields'][20]['hide_in_list'] = true;

$zz_sub['subselect']['sql'] = 'SELECT page_id, filename, t_mime.extension, version
	FROM /*_PREFIX_*/webpages_media
	LEFT JOIN /*_PREFIX_*/media USING (medium_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS o_mime USING (filetype_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS t_mime 
		ON /*_PREFIX_*/media.thumb_filetype_id = t_mime.filetype_id
	WHERE o_mime.mime_content_type = "image"
';
$zz_sub['subselect']['concat_fields'] = '';
$zz_sub['subselect']['field_suffix'][0] = '.'.$zz_setting['media_preview_size'].'.';
$zz_sub['subselect']['field_suffix'][1] = '?v=';
$zz_sub['subselect']['prefix'] = '<img src="'.$zz_setting['files_path'].'/';
$zz_sub['subselect']['suffix'] = '">';
$zz_sub['subselect']['dont_mark_search_string'] = true;

$zz_sub['sql'] = 'SELECT /*_PREFIX_*/webpages_media.*
	, /*_PREFIX_*/webpages.title AS webpage
	, CONCAT("[", /*_PREFIX_*/media.medium_id, "] ", /*_PREFIX_*/media.title) AS image
	, /*_PREFIX_*/media.filename, version
	, t_mime.extension AS thumb_extension
	FROM /*_PREFIX_*/webpages_media
	LEFT JOIN /*_PREFIX_*/webpages USING (page_id)
	LEFT JOIN /*_PREFIX_*/media USING (medium_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS t_mime 
		ON /*_PREFIX_*/media.thumb_filetype_id = t_mime.filetype_id
';
$zz_sub['sqlorder'] = ' ORDER BY /*_PREFIX_*/webpages.title DESC, sequence';

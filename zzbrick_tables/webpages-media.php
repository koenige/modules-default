<?php 

/**
 * default module
 * Table definition for 'webpages/media'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2017-2020, 2022-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['title'] = 'Webpages/Media';
$zz['table'] = '/*_PREFIX_*/webpages_media';

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'page_medium_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Page';
$zz['fields'][2]['field_name'] = 'page_id';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SELECT page_id, title, mother_page_id
	FROM /*_PREFIX_*/webpages
	ORDER BY sequence';
$zz['fields'][2]['display_field'] = 'webpage';
$zz['fields'][2]['search'] = '/*_PREFIX_*/webpages.title';
$zz['fields'][2]['show_hierarchy'] = 'mother_page_id';

$zz['fields'][4]['title'] = 'No.';
$zz['fields'][4]['field_name'] = 'sequence';
$zz['fields'][4]['type'] = 'number';
$zz['fields'][4]['auto_value'] = 'increment';
$zz['fields'][4]['def_val_ignore'] = true;
$zz['fields'][4]['exclude_from_search'] = true;

$zz['fields'][5]['title'] = 'Preview';
$zz['fields'][5]['field_name'] = 'image';
$zz['fields'][5]['type'] = 'image';
$zz['fields'][5]['class'] = 'preview';
$zz['fields'][5]['path'] = [
	'root' => wrap_setting('media_folder'), 
	'webroot' => wrap_setting('files_path'),
	'string1' => '/',
	'field1' => 'filename',
	'string2' => '.',
	'string3' => wrap_setting('media_preview_size'),
	'string4' => '.',
	'extension' => 'thumb_extension',
	'webstring1' => '?v=',
	'webfield1' => 'version'
];
$zz['fields'][5]['path']['extension_missing'] = [
	'string3' => wrap_setting('media_original_filename_extension'),
	'extension' => 'extension'
];

$zz['fields'][3]['title'] = 'Medium';
$zz['fields'][3]['field_name'] = 'medium_id';
$zz['fields'][3]['type'] = 'select';
$zz['fields'][3]['sql'] = 'SELECT /*_PREFIX_*/media.medium_id, folders.title AS folder
		, CONCAT("[", /*_PREFIX_*/media.medium_id, "] ", /*_PREFIX_*/media.title) AS image
	FROM /*_PREFIX_*/media 
	LEFT JOIN /*_PREFIX_*/media folders
		ON /*_PREFIX_*/media.main_medium_id = folders.medium_id
	WHERE /*_PREFIX_*/media.filetype_id != /*_ID filetypes folder _*/
	ORDER BY folders.title, /*_PREFIX_*/media.title';
$zz['fields'][3]['sql_character_set'] = ['utf8', 'utf8', 'utf8'];
$zz['fields'][3]['display_field'] = 'image';
$zz['fields'][3]['group'] = 'folder';
$zz['fields'][3]['exclude_from_search'] = true;

$zz['fields'][20]['title'] = 'Updated';
$zz['fields'][20]['field_name'] = 'last_update';
$zz['fields'][20]['type'] = 'timestamp';
$zz['fields'][20]['hide_in_list'] = true;

$zz['subselect']['sql'] = 'SELECT page_id, filename, version
		, t_mime.extension AS thumb_extension
		, o_mime.extension
	FROM /*_PREFIX_*/webpages_media
	LEFT JOIN /*_PREFIX_*/media USING (medium_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS o_mime USING (filetype_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS t_mime 
		ON /*_PREFIX_*/media.thumb_filetype_id = t_mime.filetype_id
	WHERE o_mime.mime_content_type = "image"
	AND /*_PREFIX_*/webpages_media.sequence = 1
';
$zz['subselect']['image'] = $zz['fields'][5]['path'];

$zz['sql'] = 'SELECT /*_PREFIX_*/webpages_media.*
	, /*_PREFIX_*/webpages.title AS webpage
	, CONCAT("[", /*_PREFIX_*/media.medium_id, "] ", /*_PREFIX_*/media.title) AS image
	, /*_PREFIX_*/media.filename, version
	, t_mime.extension AS thumb_extension
	, o_mime.extension AS extension
	FROM /*_PREFIX_*/webpages_media
	LEFT JOIN /*_PREFIX_*/webpages USING (page_id)
	LEFT JOIN /*_PREFIX_*/media USING (medium_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS o_mime USING (filetype_id)
	LEFT JOIN /*_PREFIX_*/filetypes AS t_mime 
		ON /*_PREFIX_*/media.thumb_filetype_id = t_mime.filetype_id
';
$zz['sqlorder'] = ' ORDER BY /*_PREFIX_*/webpages.title DESC, sequence';

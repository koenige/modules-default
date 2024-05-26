<?php 

/**
 * default module
 * toolinfo
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show information about upload tool
 *
 * @param array $params
 * @return array
 */
function mod_default_toolinfo($params) {
	if (empty($params) AND !empty($_GET['toolinfo'])) {
		$params[0] = $_GET['toolinfo'];
	}
	if (count($params) !== 1) return false;
	$allowed = [
		'convert' => 'ImageMagick',
		'gs' => 'GhostScript',
		'exiftool' => 'ExifTool',
		'pdfinfo' => 'pdfinfo',
		'file' => 'Unix file'
	];
	if (!in_array($params[0], array_keys($allowed))) return false;
	wrap_include('upload', 'zzform');
	$page['text'] = '<pre>'.zz_upload_binary_version($params[0]).'</pre>';
	$page['title'] = ' '.wrap_text($allowed[$params[0]]);
	$page['breadcrumbs'][]['title'] = wrap_text($allowed[$params[0]]);
	return $page;
}

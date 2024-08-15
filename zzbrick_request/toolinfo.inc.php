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
	if (count($params) !== 1) return false;
	$allowed = [
		'convert' => 'ImageMagick',
		'gs' => 'GhostScript',
		'exiftool' => 'ExifTool',
		'pdfinfo' => 'pdfinfo',
		'file' => 'Unix file',
		'mysql' => 'MySQL'
	];
	if (!in_array($params[0], array_keys($allowed))) return false;

	switch ($params[0]) {
	case 'mysql':
		$lines['Server version'] = mysqli_get_server_info(wrap_db_connection());
		$lines['Client info'] = mysqli_get_client_info();
		$lines['Protocol'] = mysqli_get_proto_info(wrap_db_connection());
		$lines['Character Encoding'] = mysqli_character_set_name(wrap_db_connection());
		$data = [];
		foreach ($lines as $key => $line)
			$data[] = sprintf('%s: %s', wrap_text($key), $line);
		$page['text'] = '<pre>'.implode("\n", $data).'</pre>';
		break;
	default:
		wrap_include('upload', 'zzform');
		$page['text'] = '<pre>'.zz_upload_binary_version($params[0]).'</pre>';
		break;
	}
	$page['title'] = wrap_text($allowed[$params[0]]);
	$page['breadcrumbs'][]['title'] = wrap_text($allowed[$params[0]]);
	return $page;
}

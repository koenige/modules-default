<?php 

/**
 * default module
 * Maintenance script for database operations with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Maintenance script for zzform to do some cleanup/correction operations
 *
 * - change database name if local development uses different database names
 * for relations and translations
 * - delete files from backup-directory
 * - enter an sql query
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_maintenance($params) {
	wrap_access_quit('default_maintenance');

	wrap_setting('dont_show_h1', false); // internal, no need to hide it
	wrap_setting_add('extra_http_headers', 'X-Frame-Options: Deny');
	wrap_setting_add('extra_http_headers', "Content-Security-Policy: frame-ancestors 'self'");

	if (isset($brick['page'])) $page = $brick['page'];
	$page['head'] = isset($page['head']) ? $page['head'] : '';
	if (wrap_setting('zzform_no_packagecss'))
		$page['head'] .= wrap_template('zzform-head');
	else
		wrap_package_activate('zzform'); // for CSS
	$page['extra']['css'][] = 'default/maintenance.css';
	if (!empty($_GET) OR !empty($_POST)) {
		$page['breadcrumbs'][] = ['title' => wrap_text('Maintenance'), 'url_path' => './'];
	}

	if (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif ($type = mod_default_maintenance_keycheck()) {
		$brick = '%%% '.$type['verb'].' '.$type['key'].' '.($_GET[$type['key']] ?? '').' %%%';
		$newpage = brick_format($brick);
		if ($newpage['status'] === 404) return $newpage;
		if (isset($newpage['content_type']) AND $newpage['content_type'] !== 'html') return $newpage;
		$page['title'] = $newpage['title'];
		if (wrap_setting('breadcrumbs_h1_prefix') AND is_numeric(wrap_setting('breadcrumbs_h1_prefix')))
			wrap_setting('breadcrumbs_h1_prefix', wrap_setting('breadcrumbs_h1_prefix') + 1);
		else
			wrap_setting('breadcrumbs_h1_prefix', 1);
		$page['text'] = $newpage['text'];
		$page['status'] = $newpage['status'];
		$page['extra'] = array_merge_recursive($page['extra'], $newpage['extra'] ?? []);
		$page['breadcrumbs'] = array_merge($page['breadcrumbs'], $newpage['breadcrumbs']);
		if (!empty($newpage['query_strings']))
			$page['query_strings'] = $newpage['query_strings'];
		if (!empty($newpage['head']))
			$page['head'] .= $newpage['head'];
		$page['query_strings'][] = $type['key'];
		return $page;
	}

	$data = [];
	$data = array_merge($data, mod_default_maintenance_tables());
	$data['php_version'] = phpversion();
	wrap_include('upload', 'zzform');
	$functions = ['convert', 'gs', 'exiftool', 'file', 'ffmpeg'];
	// @todo check why 'pdfinfo' does not return anything
	foreach ($functions as $function) {
		$full = zz_upload_binary_version($function, false);
		$data[$function] = explode("\n", $full);
		if ($function === 'convert') {
			$data['convert'] = str_replace('Version: ', '', $data['convert']);
			$data['convert'] = str_replace('https://imagemagick.org', '', $data['convert']);
		}
		$data[$function] = $data[$function][0];
	}
	$data['mysql'] = mysqli_get_server_info(wrap_db_connection());
	$data['curl'] = mod_default_maintenance_curl();

	$page['text'] = wrap_template('maintenance', $data);
	$page['title'] = wrap_text('Maintenance');
	$page['breadcrumbs'][]['title'] = wrap_text('Maintenance');
	return $page;
}

/**
 * check script depending on GET variable
 *
 * @return string
 */
function mod_default_maintenance_keycheck() {
	$keys = [
		'cachedircheck' => 'make',
		'dbmodules' => 'make',
		'dbupdate' => 'make',
		'filetree' => 'make',
		'integritycheck' => 'request',
		'log' => 'make',
		'loggingadd' => 'make',
		'loggingread' => 'request',
		'maillog' => 'make',
		'serversync_development' => 'make',
		'sqlquery' => 'make',
		'toolinfo' => 'request',
		'translationscheck' => 'make',
		'deprecations' => 'make',
		'textupdate' => 'make'
	];
	foreach ($keys as $key => $verb) {
		if (isset($_GET[$key])) return ['key' => $key, 'verb' => $verb];
		if (isset($_POST[$key])) return ['key' => $key, 'verb' => $verb];
	}
	return '';
}

/**
 * list and modify databases for translation and relation tables
 *
 * @return array
 */
function mod_default_maintenance_tables() {
	$data = [];

	if (!wrap_setting('zzform_check_referential_integrity') AND !wrap_setting('translate_fields'))
		return $data;

	$types = [];
	if (wrap_setting('zzform_check_referential_integrity')) {
		$types['master'] = [
			'title' => wrap_text('Master'),
			'db_table' => '/*_TABLE zzform_relations _*/',
			'db_field' => 'master_db',
		];
		$types['detail'] = [
			'title' => wrap_text('Detail'),
			'db_table' => '/*_TABLE zzform_relations _*/',
			'db_field' => 'detail_db',
		];
	}
	if (wrap_setting('translate_fields')) {
		$types['translation'] = [
			'title' => wrap_text('Translation'),
			'db_table' => '/*_TABLE default_translationfields _*/',
			'db_field' => 'db_name',
		];
	}

	// Update
	if ($_POST AND !empty($_POST['db_value'])) {
		foreach ($types as $category => $type) {
			if (empty($_POST['db_value'][$category])) continue;
			foreach ($_POST['db_value'][$category] as $old => $new) {
				if (empty($_POST['db_set'][$category][$old])) continue;
				if ($_POST['db_set'][$category][$old] != 'change') continue;
				$sql = 'UPDATE %s SET %s = "%s" WHERE %s = "%s"';
				$sql = sprintf($sql, $type['db_table'],
					$type['db_field'], wrap_db_escape($new),
					$type['db_field'], wrap_db_escape($old)
				);
				wrap_db_query($sql);
			}
		}
		wrap_redirect_change();
	}

	// All available databases
	$sql = 'SHOW DATABASES';
	$databases = wrap_db_fetch($sql, 'Databases', 'single value');
	$db_list = [];
	foreach ($databases as $db) {
		// no system databases
		if (in_array($db, ['information_schema'])) continue;
		$db_list[] = [
			'db' => $db,
			'prefered' => $db === wrap_setting('db_name') ? true : false
		];
	}

	$data['tables'] = [];
	foreach ($types as $category => $type) {
		$sql = sprintf('SELECT DISTINCT %s FROM %s', $type['db_field'], $type['db_table']);
		foreach (wrap_db_fetch($sql, $type['db_field'], 'single value') as $db) {
			$data['tables'][] = [
				'title' => $type['title'],
				'db' => $db,
				'category' => $category,
				'keep' => in_array($db, $databases),
				'databases' => []
			];
		}
	}

	$data['database_changeable'] = mod_default_maintenance_database_changeable($data['tables'], $db_list);
	if ($data['database_changeable']) {
		foreach ($data['tables'] as &$table) {
			$table['databases'] = $db_list;
		}
		unset($table);
	}
	return $data;
}

/**
 * should relation/translation database names be editable?
 *
 * @param array $tables rows built for the maintenance table
 * @param array $db_list databases available on the server (for dropdown)
 * @return bool
 */
function mod_default_maintenance_database_changeable(array $tables, array $db_list) {
	if (count($db_list) > 1) return true;

	$server_databases = array_column($db_list, 'db');
	if (!in_array(wrap_setting('db_name'), $server_databases, true)) return true;
	foreach ($tables as $table) {
		if (!in_array($table['db'], $server_databases, true)) return true;
	}
	return false;
}

/**
 * get cURL version
 *
 * @return string
 */
function mod_default_maintenance_curl() {
	if (!function_exists('curl_version')) return wrap_text('not installed');
	$version = curl_version();
	$details = [];
	foreach ($version as $key => $value) {
		if (!str_ends_with($key, '_version')) continue;
		$detail = substr($key, 0, -8);
		if (false === stripos($value, $detail)) $value = $detail.'/'.$value;
		$details[] = $value;
	}
	$text = implode(' ', $details);
	$text = sprintf('%s (%s)', $version['version'], $text);
	return $text;
}

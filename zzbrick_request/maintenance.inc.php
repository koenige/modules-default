<?php 

/**
 * default module
 * Maintenance script for database operations with zzform
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
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
 * @global array $zz_conf configuration variables
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
	$page['title'] = wrap_setting('zzform_heading_prefix') ? wrap_text(wrap_setting('zzform_heading_prefix')) : '';
	$page['extra']['css'][] = 'default/maintenance.css';
	if (!empty($_GET) OR !empty($_POST)) {
		$page['title'] .= ' <a href="./">'.wrap_text('Maintenance').'</a>:';
		$page['breadcrumbs'][] = ['title' => wrap_text('Maintenance'), 'url_path' => './'];
	}

	if (isset($_GET['phpinfo'])) {
		phpinfo();
		exit;
	} elseif ($type = zz_maintenance_keycheck()) {
		$brick = '%%% '.$type['verb'].' '.$type['key'].' '.($_GET[$type['key']] ?? '').' %%%';
		$newpage = brick_format($brick);
		if ($newpage['status'] === 404) return $newpage;
		if (isset($newpage['content_type']) AND $newpage['content_type'] !== 'html') return $newpage;
		$page['title'] .= ' '.$newpage['title'];
		$page['text'] = $newpage['text'];
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
	$data = array_merge($data, zz_maintenance_tables());
	$data['php_version'] = phpversion();
	wrap_include('upload', 'zzform');
	$functions = ['convert', 'gs', 'exiftool', 'file'];
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

	$page['text'] = wrap_template('maintenance', $data);
	$page['title'] .= ' '.wrap_text('Maintenance');
	$page['breadcrumbs'][]['title'] = wrap_text('Maintenance');
	return $page;
}

/**
 * check script depending on GET variable
 *
 * @return string
 */
function zz_maintenance_keycheck() {
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
		'translationscheck' => 'make'
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
function zz_maintenance_tables() {
	$data = [];

	if (!wrap_setting('zzform_check_referential_integrity') AND !wrap_setting('translate_fields'))
		return $data;
		
	// Update
	if ($_POST AND !empty($_POST['db_value'])) {
		$areas = ['master', 'detail', 'translation'];
		foreach ($areas as $area) {
			if (!empty($_POST['db_value'][$area])) {
				foreach ($_POST['db_value'][$area] as $old => $new) {
					if (empty($_POST['db_set'][$area][$old])) continue;
					if ($_POST['db_set'][$area][$old] != 'change') continue;
					if ($area === 'translation') {
						$table = wrap_sql_table('default_translationfields');
						$field_name = 'db_name';
					} else {
						$table = wrap_sql_table('zzform_relations');
						$field_name = $area.'_db';
					}
					$sql = 'UPDATE %s SET %s = "%s" WHERE %s = "%s"';
					$sql = sprintf($sql, $table,
						$field_name, wrap_db_escape($new),
						$field_name, wrap_db_escape($old)
					);
					wrap_db_query($sql);
				}
			}
		}
		wrap_redirect_change();
	}
	if (wrap_setting('zzform_check_referential_integrity')) {
	// Master database
		$sql = 'SELECT DISTINCT master_db FROM %s';
		$sql = sprintf($sql, wrap_sql_table('zzform_relations'));
		$dbs['master'] = wrap_db_fetch($sql, 'master_db', 'single value');

	// Detail database	
		$sql = 'SELECT DISTINCT detail_db FROM %s';
		$sql = sprintf($sql, wrap_sql_table('zzform_relations'));
		$dbs['detail'] = wrap_db_fetch($sql, 'detail_db', 'single value');
	}

	if (wrap_setting('translate_fields')) {
	// Translations database	
		$sql = 'SELECT DISTINCT db_name FROM %s';
		$sql = sprintf($sql, wrap_sql_table('default_translationfields'));
		$dbs['translation'] = wrap_db_fetch($sql, 'db_name', 'single value');
	}
	
	// All available databases
	$sql = 'SHOW DATABASES';
	$databases = wrap_db_fetch($sql, 'Databases', 'single value');
	foreach ($databases as $db) {
		// no system databases
		if (in_array($db, ['information_schema'])) continue;
		$db_list[] = [
			'db' => $db,
			'prefered' => $db === wrap_setting('db_name') ? true : false
		];
	}
	$data['database_changeable'] = false;
	if (count($db_list) > 1) {
		$data['database_changeable'] = true;
	} else {
		foreach ($dbs as $db) {
			if (reset($db) === wrap_setting('db_name')) continue;
			$data['database_changeable'] = true;
			break;
		}
	}
		
	$i = 0;
	foreach ($dbs as $category => $db_names) {
		foreach ($db_names as $db) {
			$data['tables'][] = [
				'title' => wrap_text(ucfirst($category)),
				'db' => $db,
				'category' => $category,
				'keep' => in_array($db, $databases) ? true : false,
				'databases' => $data['database_changeable'] ? $db_list : []
			];
		}
	}
	return $data;
}

<?php 

/**
 * default module
 * manage modules
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * manage modules
 *
 * @param array $params
 * @global array $zz_setting
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_dbmodules($params) {
	global $zz_setting;

	$data = [];
	foreach ($zz_setting['modules'] as $module) {
		$readme_file = sprintf('%s/%s/README.md', $zz_setting['modules_dir'], $module);
		$install_file = sprintf('%s/%s/docs/sql/install.sql', $zz_setting['modules_dir'], $module);
		$cfg_file = sprintf('%s/%s/docs/sql/settings.cfg', $zz_setting['modules_dir'], $module);
		$data['modules'][$module] = [
			'module' => $module,
			'install_sql' => file_exists($install_file) ? $install_file : false,
			'install_date' => wrap_get_setting('mod_'.$module.'_install_date'),
			'settings_cfg' => file_exists($cfg_file) ? $cfg_file : false,
			'readme' => file_exists($readme_file) ? $readme_file : false,
			'enabled' => 1
		];
	}
	if (!empty($_GET['readme'])) {
		if (array_key_exists($_GET['readme'], $data['modules']))
			$data['readme'] = file_get_contents($data['modules'][$_GET['readme']]['readme']);
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		require_once $zz_setting['core'].'/install.inc.php';
	 	if (!empty($_POST['install'])) {
			$module = key($_POST['install']);
			if (empty($data['modules'][$module]['install_date'])) {
				wrap_install_module($module);
				$data['modules'][$module]['install_date'] = date('Y-m-d H:i:s');
				$data['install_settings'] = wrap_install_settings_page($module);
				if (!$data['install_settings']) return wrap_redirect_change();
			}
		} else {
			wrap_install_settings_write();
			return wrap_redirect_change();
		}
	}

	$data['modules'] = array_values($data['modules']);
	$page['text'] = wrap_template('dbmodules', $data);
	$page['query_strings'][] = 'readme';
	if (!empty($data['install_settings'])) {
		$page['title'] =  '<a href="?dbmodules">'.wrap_text('Modules').'</a> – '.wrap_text('Install Settings');
		$page['breadcrumbs'][] = '<a href="?dbmodules">'.wrap_text('Modules').'</a>';
		$page['breadcrumbs'][] = wrap_text('Install Settings');
		$page['head'] = '<style type="text/css"> input[type=text] {width: 30em; } dt { margin: 1em 0 0; }</style>';
	} else {
		$page['title'] = wrap_text('Modules');
		$page['breadcrumbs'][] = wrap_text('Modules');
	}
	return $page;
}

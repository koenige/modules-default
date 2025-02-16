<?php 

/**
 * default module
 * manage modules
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * manage modules
 *
 * @param array $params
 * @return array $page
 *		'text' => page content, 'title', 'breadcrumbs', ...
 */
function mod_default_make_dbmodules($params) {
	$data = [];
	$files['readme'] = wrap_collect_files('./README.md', 'modules');
	$files['install'] = wrap_collect_files('configuration/install.sql', 'modules');
	$files['settings'] = wrap_collect_files('configuration/settings.cfg', 'modules');
	foreach (wrap_setting('modules') as $module) {
		$data['modules'][$module] = [
			'module' => $module,
			'install_sql' => !empty($files['install'][$module]) ? $files['install'][$module] : false,
			'install_date' => wrap_setting('mod_'.$module.'_install_date'),
			'settings_cfg' => !empty($files['settings'][$module]) ? $files['settings'][$module] : false,
			'readme' => !empty($files['readme'][$module]) ? $files['readme'][$module] : false,
			'enabled' => 1
		];
	}
	if (!empty($_GET['readme']) AND array_key_exists($_GET['readme'], $data['modules'])) {
		$data['readme'] = file_get_contents($data['modules'][$_GET['readme']]['readme']);
		$data['readme'] = preg_replace('/%%%(.*?)%%%/s', '%%% explain $1%%%', $data['readme']);
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		wrap_include('install', 'zzwrap');
	 	if (!empty($_POST['install'])) {
			$module = key($_POST['install']);
			if (empty($data['modules'][$module]['install_date'])) {
				wrap_install_module($module);
				$data['modules'][$module]['install_date'] = date('Y-m-d H:i:s');
				$data['install_settings'] = wrap_install_settings_page($module);
				if (!$data['install_settings']) wrap_redirect_change();
			}
		} else {
			wrap_install_settings_write();
			wrap_redirect_change();
		}
	}

	$data['modules'] = array_values($data['modules']);
	$page['text'] = wrap_template('dbmodules', $data);
	$page['query_strings'][] = 'readme';
	if (!empty($data['install_settings'])) {
		$page['title'] = '<a href="?dbmodules">'.wrap_text('Modules').'</a> – '.wrap_text('Install Settings');
		$page['breadcrumbs'][] = ['title' => wrap_text('Modules'), 'url_path' => '?dbmodules'];
		$page['breadcrumbs'][]['title'] = wrap_text('Install Settings');
		$page['head'] = '<style type="text/css"> input[type=text] {width: 30em; } dt { margin: 1em 0 0; }</style>';
	} else {
		$page['title'] = wrap_text('Modules');
		$page['breadcrumbs'][]['title'] = wrap_text('Modules');
	}
	return $page;
}

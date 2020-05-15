<?php

/**
 * default module
 * Database form for website settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2018, 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require __DIR__.'/../zzbrick_tables/settings.php';

$cfg_file_template = sprintf('%s/%%s/docs/sql/settings.cfg', $zz_setting['modules_dir']);
$cfg = [];
foreach ($zz_setting['modules'] as $module) {
	$cfg_file = sprintf($cfg_file_template, $module);
	if (!file_exists($cfg_file)) continue;
	$cfg += parse_ini_file($cfg_file, true);
}

// key
$zz['fields'][3]['cfg'] = $cfg;
$zz['fields'][3]['dependencies'] = [6, 4]; // description
$zz['fields'][3]['dependencies_function'] = 'zz_cfg_read';


function zz_cfg_read($cfg) {
	if (!array_key_exists('description', $cfg)) $cfg['description'] = '';
	if (!array_key_exists('default', $cfg)) $cfg['default'] = '';
	return [
		$cfg['description'],
		$cfg['default']
	];
}

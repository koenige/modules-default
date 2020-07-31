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

$cfg = wrap_setting_cfg();

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

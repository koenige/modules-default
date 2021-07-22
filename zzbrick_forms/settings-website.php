<?php

/**
 * default module
 * Database form for website settings
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015, 2018, 2020-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_access('default_settings')) wrap_quit(403);

require __DIR__.'/../zzbrick_tables/settings.php';

// key
$zz['fields'][3]['cfg'] = wrap_cfg_files('settings');
$zz['fields'][3]['dependencies'] = [6, 4]; // description
$zz['fields'][3]['dependencies_function'] = 'zz_cfg_read';


function zz_cfg_read($cfg) {
	if (!array_key_exists('description', $cfg)) $cfg['description'] = '';
	if (!array_key_exists('default', $cfg)) $cfg['default'] = '';
	if (!empty($cfg['type'] AND $cfg['type'] === 'random')) {
		$cfg['default'] = wrap_random_hash(42, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+$%_/& ');
	}
	return [
		$cfg['description'],
		$cfg['default']
	];
}

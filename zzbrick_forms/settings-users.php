<?php

/**
 * default module
 * Database form for user settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require __DIR__.'/../zzbrick_tables/settings.php';

$zz['title'] = 'User settings';

$zz['fields'][2]['required'] = true;

$zz['fields'][4]['null_string'] = false;

$zz['fields'][6]['hide_in_list'] = true;
$zz['fields'][6]['hide_in_form'] = true;

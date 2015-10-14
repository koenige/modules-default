<?php

/**
 * default module
 * Database form for website settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014-2015 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require __DIR__.'/../zzbrick_tables/settings.php';

$zz['title'] = 'Website settings';

unset($zz['fields'][2]); // login_id

$zz['fields'][3]['class'] = 'block480a';
$zz['fields'][4]['list_append_next'] = true;
$zz['fields'][4]['class'] = 'block480';

$zz['sql'] = 'SELECT /*_PREFIX_*/_settings.*
	FROM /*_PREFIX_*/_settings';
$zz['sqlorder'] = ' ORDER BY setting_key, setting_value';

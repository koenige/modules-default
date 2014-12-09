<?php

/**
 * default module
 * Database form for website settings
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2014 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


require __DIR__.'/../zzbrick_tables/settings.php';

$zz['title'] = 'Website settings';

unset($zz['fields'][2]); // login_id

$zz['fields'][4]['list_append_next'] = true;
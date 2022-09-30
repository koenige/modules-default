<?php 

/**
 * default module
 * form script for 'webpages'
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$zz['where']['website_id'] =
$values['website_id'] = $brick['data']['website_id'];

$zz = zzform_include_table('webpages', $values);

// @todo access restriction per website

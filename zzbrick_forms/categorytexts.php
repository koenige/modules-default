<?php

/**
 * default module
 * Texts for one category
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


$category_id = $brick['vars'][0] ?? 0;
$categories = mf_default_category_text_categories();
if (!$category_id OR empty($categories[$category_id]))
	wrap_quit(404);

$zz = zzform_include('categories-text');
$zz['where']['category_id'] = $category_id;
$zz['fields'][3]['show_hierarchy_subtree'] = wrap_category_id('texts/'.$categories[$category_id]);

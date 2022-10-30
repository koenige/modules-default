<?php 

/**
 * default module
 * show SESSION contact
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * show SESSION contact
 *
 * @param $params void
 * @return string
 */
function page_sessioncontact($params) {
	if (empty($_SESSION['contact'])) return '';
	return $_SESSION['contact'];
}

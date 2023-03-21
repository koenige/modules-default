<?php

/**
 * default module
 * output mail address with name
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2016, 2021-2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * %%% request mailaddress %%%
 * %%% request mailaddress "First Last" %%%
 * %%% request mailaddress "First Last" test@example.org %%%
 *
 * @param array $params
 * @return array
 */
function mod_default_mailaddress($params) {
	$name = wrap_setting('own_name');
	$mail = wrap_setting('own_e_mail');
	
	if (count($params) === 2) {
		if (wrap_mail_valid($params[1], false)) {
			$mail = $params[1];
			$name = $params[0];
		} elseif (wrap_mail_valid($params[0], false)) {
			$mail = $params[0];
			$name = $params[1];
		} else {
			$name = implode(' ', $params);
		}
	} elseif (count($params) === 1) {
		if (wrap_mail_valid($params[0], false)) {
			$mail = $params[0];
		} else {
			$name = $params[0];
		}
	} elseif (count($params)) {
		return false;
	}
	if ($name) {
		$page['text'] = wrap_mailto($name, $mail);
	} else {
		$page['text'] = markdown_inline(sprintf('<%s>', $mail));
	}
	return $page;
}

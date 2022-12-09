<?php 

/**
 * default module
 * page elements: language switcher
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2015, 2018-2019, 2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/** 
 * language switcher
 * 
 * @param array $params (HTML-Code, if value will be returned)
 * @param array $page
 * @return string $text
 */
function page_languagelink($params, $page) {
	global $zz_setting;

	// language switcher makes only sense for valid URLs
	if (!empty($page['status']) AND $page['status'] !== 200) return '';
	if (!wrap_get_setting('languages_allowed')) return '';
	
	$link = $zz_setting['request_uri'];
	if (str_starts_with($link, '/'.$zz_setting['lang']))
		$link = substr($link, 3);
	$links_translated = wrap_translate_url_other();
	
	foreach (wrap_get_setting('languages_allowed') as $lang) {
		$languages[] = [
			'iso' => $lang,
			'language' => $zz_setting['languages_names'][$lang] ?? $lang
		];
	}

	foreach ($languages as $index => $values) {
		if ($values['iso'] === $zz_setting['lang']) continue;
		$languages[$index]['link'] = $links_translated[$values['iso']] ?? $link;
	}
	
	$text = wrap_template('languagelink', $languages);
	return $text;
}

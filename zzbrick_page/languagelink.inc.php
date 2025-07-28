<?php 

/**
 * default module
 * page elements: language switcher
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010-2015, 2018-2019, 2022-2023, 2025 Gustaf Mossakowski
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
	// language switcher makes only sense for valid URLs
	if (!empty($page['status']) AND $page['status'] !== 200) return '';
	if (!wrap_setting('languages_allowed')) return '';
	
	$link = wrap_setting('request_uri');
	if (str_starts_with($link, '/'.wrap_setting('lang')))
		$link = substr($link, 3);
	$links_translated = wrap_translate_url_other();
	if (array_key_exists(wrap_setting('default_source_language'), $links_translated))
		$link = $links_translated[wrap_setting('default_source_language')];
	
	foreach (wrap_setting('languages_allowed') as $lang) {
		if (in_array($lang, wrap_setting('languages_hidden'))) continue;
		$languages[] = [
			'iso' => $lang,
			'language' => wrap_setting('languages_names['.$lang.']') ?? $lang
		];
	}
	if (!$languages) return '';

	foreach ($languages as $index => $values) {
		if ($values['iso'] === wrap_setting('lang')) continue;
		$languages[$index]['link'] = $links_translated[$values['iso']] ?? $link;
	}
	
	return wrap_template('languagelink', $languages);
}

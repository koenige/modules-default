<?php 

/**
 * default module
 * sitemap.xml from cache
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Creates a sitemap.xml file from cache files
 * this is not the best solution, but it should work if a website is visited often
 * e. g. by crawlers
 *
 * @param array $params
 * @param array $settings
 * @return array
 */
function mod_default_sitemap($params, $settings) {
	$sql = 'SELECT identifier FROM webpages WHERE parameters LIKE "%&search=0%"';
	$excluded = wrap_db_fetch($sql, 'identifier', 'single value');
	$excluded = array_values($excluded);
	foreach ($excluded as $index => $identifier)
		if (str_ends_with($identifier, '*')) $excluded[$index] = substr($identifier, 0, -1);
	
	$languages_allowed = wrap_setting('languages_allowed');
	$languages_hidden = wrap_setting('languages_hidden');

	$dir = sprintf('%s/%s', wrap_setting('cache_dir'), wrap_setting('hostname'));
	$files = scandir($dir);
	$urls = [];
	foreach ($files as $index => $file) {
		$file = urldecode($file);
		if (str_ends_with($file, '.headers')) continue;
		if (str_starts_with($file, '.')) continue;
		if (str_starts_with($file, wrap_setting('layout_path'))) continue;
		if (str_starts_with($file, wrap_setting('behaviour_path'))) continue;
		if (str_starts_with($file, '/robots.txt')) continue;
		foreach ($excluded as $identifier) {
			if (str_starts_with($file, $identifier)) continue 2;
			foreach ($languages_allowed as $language)
				if (str_starts_with($file, sprintf('/%s%s', $language, $identifier))) continue 3;
		}
		$query = parse_url($file, PHP_URL_QUERY);
		if ($query) {
			parse_str($query, $query);
			if (array_key_exists('url', $query)) continue;
			// no lang= in URL if it is just a one language website
			if (!$languages_allowed AND array_key_exists('lang', $query)) continue;
			// no lang= in URL if there is already a language in the path
			foreach ($languages_allowed as $language) {
				if (!str_starts_with($file, '/'.$language)) continue;
				if (array_key_exists('lang', $query)) continue 2;
			}
		}
		foreach ($languages_hidden as $language)
			if (str_starts_with($file, sprintf('/%s/', $language))) continue 2;

		$urls[$index]['url'] = wrap_setting('host_base').$file;
		$urls[$index]['lastmod'] = date('Y-m-d', filemtime($dir.'/'.$files[$index]));
	}
	$output = $settings['output'] ?? 'xml';
	switch ($output) {
		case 'txt': return mod_default_sitemap_txt($urls);
		case 'xml': default: return mod_default_sitemap_xml($urls);
	}
}

function mod_default_sitemap_txt($urls) {
	$page['content_type'] = 'txt';
	$page['text'] = '';
	foreach ($urls as $url)
		$page['text'] .= $url['url']."\r\n";
	return $page;
}

function mod_default_sitemap_xml($urls) {
	$page['content_type'] = 'xml';
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->formatOutput = true;
	$root = $doc->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
	$doc->appendChild($root);
	foreach ($urls as $url) {
		$element = $doc->createElement('url');
		$root->appendChild($element);
		$loc = $doc->createElement('loc');
		$text = $doc->createTextNode($url['url']);
		$loc->appendChild($text);
		$element->appendChild($loc);
		if (!empty($url['lastmod'])) {
			$lastmod = $doc->createElement('lastmod');
			$text = $doc->createTextNode($url['lastmod']);
			$lastmod->appendChild($text);
			$element->appendChild($lastmod);
		}
		// @todo support <changefreq>monthly</changefreq>
		// @todo support <priority>0.8</priority>
	}
	$page['text'] = $doc->saveXML();
	return $page;
}

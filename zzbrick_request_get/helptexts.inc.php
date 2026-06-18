<?php 

/**
 * default module
 * Help texts, overview
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_get_helptexts($params) {
	$files = mf_default_helptexts_files();
	$data = [];
	foreach ($files as $identifier => $variants) {
		if (!empty($params[0]) AND $identifier !== $params[0]) continue;
		foreach ($variants as $variant) {
			if (array_key_exists($identifier, $data))
				if (!mf_default_helptexts_better($variant, $data[$identifier])) continue;
			$data[$identifier] = $variant;
		}
		$data[$identifier] = mf_default_helptexts_content($data[$identifier]);
	}
	$data = array_values($data);
	foreach ($data as $index => $text) {
		if ($text['language'] !== wrap_setting('lang'))
			$data[$index]['foreign_language'] = true;
	}
	if (!empty($params[0])) return $data[0] ?? [];
	return $data;
}

/**
 * get all help files
 *
 * @return array
 */
function mf_default_helptexts_files() {
	$files = wrap_collect_files('help/*.{txt,md}');

	foreach ($files as $package => $file) {
		$basename = basename($file);
		$extension = wrap_file_extension($file);
		if ($extension)
			$basename = substr($basename, 0, -strlen($extension) -1);
		$lang = '';
		if (strstr($basename, '-')) {
			$basename = explode('-', $basename);
			if (strlen(end($basename)) === 2) {
				$lang = array_pop($basename);
			}
			$basename = implode('-', $basename);
		}
		$title = $basename;
		$basename = mf_default_helptexts_identifier($basename);
		$data[$basename][] = [
			'title' => $title,
			'language' => $lang,
			'package' => substr($package, 0, strrpos($package, '/')),
			'filename' => $file,
			'identifier' => $basename,
			'type' => $extension
		];
	}
	return $data;
}

/**
 * check if help file is more important than existing in list
 * i. e. language better, package not default
 *
 * @param array $new
 * @param array $existing
 * @return bool true = better
 */
function mf_default_helptexts_better($new, $existing) {
	// check language
	if ($new['language'] === wrap_setting('lang')) return true;
	if ($new['language'] === '' AND $existing['language'] !== wrap_setting('lang')) return true;
	// check package
	if ($existing['package'] === 'default' AND $new['package'] !== 'default') return true;
	return false;
}

/**
 * get content of helptext file, set title
 *
 * @param array $file
 * @return array
 */
function mf_default_helptexts_content($file) {
	$file['text'] = file_get_contents($file['filename']);
	$file['text'] = preg_replace('/<!--[\s\S]*?-->/', '', $file['text']);
	$file['text'] = preg_replace('/%%%(.*?)%%%/s', '%%% explain $1%%%', $file['text']);
	$file['text'] = mf_default_helptexts_links($file['text']);

	if ($file['type'] === 'md') {
		preg_match('/# (.+)/', $file['text'], $matches);
		if ($matches[1]) $file['title'] = $matches[1];
	}
	return $file;
}

/**
 * helptext identifier from a file or link name (lowercase, no extension)
 *
 * @param string $name e.g. "Format.md", "Page Elements", "format"
 * @return string e.g. "format", "page-elements"
 */
function mf_default_helptexts_identifier($name) {
	$basename = basename($name);
	$extension = wrap_file_extension($basename);
	if ($extension)
		$basename = substr($basename, 0, -strlen($extension) - 1);
	if (strstr($basename, '-')) {
		$parts = explode('-', $basename);
		if (strlen(end($parts)) === 2) {
			array_pop($parts);
			$basename = implode('-', $parts);
		}
	}
	return wrap_filename(strtolower(wrap_normalize($basename)));
}

/**
 * convert markdown links to other help texts into site paths
 *
 * [Format.md](Format.md) and [Format](format) both link to the same page.
 *
 * @param string $text
 * @return string
 */
function mf_default_helptexts_links($text) {
	static $files = null;
	if ($files === null)
		$files = mf_default_helptexts_files();

	return preg_replace_callback(
		'/\[([^\]]+)\]\(([^)\s]+(?:\s+"[^"]*")?)\)/',
		function ($match) use ($files) {
			$link_text = $match[1];
			$url = $match[2];
			$title = '';
			if (preg_match('/^([^\s]+)(?:\s+"([^"]*)")?$/', $url, $parts)) {
				$url = $parts[1];
				$title = $parts[2] ?? '';
			}
			if (preg_match('#^[a-z]+:#i', $url)) return $match[0];
			if (str_starts_with($url, '/') || str_starts_with($url, '#')) return $match[0];

			$anchor = '';
			if (($pos = strpos($url, '#')) !== false) {
				$anchor = substr($url, $pos);
				$url = substr($url, 0, $pos);
			}
			$identifier = mf_default_helptexts_identifier($url);
			if (!$identifier || !array_key_exists($identifier, $files)) return $match[0];

			$path = wrap_path('default_helptext', $identifier);
			if (!$path) return $match[0];

			$output = sprintf('[%s](%s%s', $link_text, $path, $anchor);
			if ($title) $output .= ' "'.$title.'"';
			$output .= ')';
			return $output;
		},
		$text
	);
}

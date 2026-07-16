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


function mod_default_get_help($params) {
	$files = mf_default_help_files();
	if (count($params) === 2)
		return mf_default_help_pick($files, $params[1], $params[0]) ?? [];
	if (count($params) === 1)
		return mf_default_help_pick($files, $params[0]) ?? [];
	return mf_default_help_all($files);
}

/**
 * get all help files
 *
 * @return array
 */
function mf_default_help_files() {
	$files = wrap_collect_files('help/*.{txt,md}');

	foreach ($files as $package => $file) {
		$basename = basename($file);
		$extension = wrap_file_extension($file);
		if ($extension)
			$basename = substr($basename, 0, -strlen($extension) -1);
		$lang = NULL;
		if (strstr($basename, '-')) {
			$basename = explode('-', $basename);
			if (strlen(end($basename)) === 2) {
				$lang = array_pop($basename);
			}
			$basename = implode('-', $basename);
		}
		$title = $basename;
		$basename = mf_default_help_identifier($basename);
		$data[$basename][] = [
			'title' => $title,
			'language' => $lang ?? 'en',
			'package' => substr($package, 0, strrpos($package, '/')),
			'filename' => $file,
			'identifier' => $basename,
			'type' => $extension
		];
	}
	return $data ?? [];
}

/**
 * packages with number of help texts (language-aware, one per identifier)
 *
 * @return array
 */
function mf_default_help_packages() {
	$files = mf_default_help_files();
	$names = [];
	foreach ($files as $variants) {
		foreach ($variants as $variant)
			$names[$variant['package']] = true;
	}
	$packages = [];
	foreach (array_keys($names) as $package) {
		$pkg = wrap_cfg_files('package', ['package' => $package, 'translate' => true]);
		$entry = [
			'package' => $package,
			'name' => $pkg['about']['name'] ?? $package,
			'count' => count(mf_default_help_list($package))
		];
		$tagline = trim((string) ($pkg['about']['tagline'] ?? ''));
		if ($tagline) $entry['tagline'] = $tagline;
		$packages[] = $entry;
	}
	usort($packages, fn($a, $b) => strcmp($a['name'], $b['name']));
	return $packages;
}

/**
 * help texts for one package
 *
 * @param string $package
 * @return array
 */
function mf_default_help_list($package) {
	$files = mf_default_help_files();
	$data = [];
	foreach ($files as $identifier => $variants) {
		$variant = mf_default_help_pick_variant($variants, $package);
		if (!$variant) continue;
		$data[] = mf_default_help_content($variant);
	}
	usort($data, fn($a, $b) => strcmp($a['title'], $b['title']));
	foreach ($data as $index => $text) {
		if ($text['language'] !== wrap_setting('lang'))
			$data[$index]['foreign_language'] = true;
	}
	return $data;
}

/**
 * help texts for one package, grouped by audience
 *
 * @param string $package
 * @return array list of ['audience', 'title', 'texts']
 */
function mf_default_help_list_grouped($package) {
	$texts = mf_default_help_list($package);
	if (!$texts) return [];

	$order = mf_default_help_audiences();
	$buckets = array_fill_keys($order, []);
	$general = [];
	foreach ($texts as $text) {
		if (empty($text['audience'])) {
			$general[] = $text;
			continue;
		}
		foreach ($text['audience'] as $audience) {
			if (!array_key_exists($audience, $buckets)) continue;
			$buckets[$audience][] = $text;
		}
	}

	$groups = [];
	foreach ($order as $audience) {
		if (!$buckets[$audience]) continue;
		usort($buckets[$audience], fn($a, $b) => strcmp($a['title'], $b['title']));
		$groups[] = [
			'audience' => $audience,
			'title' => mf_default_help_audience_title($audience),
			'texts' => $buckets[$audience],
		];
	}
	if ($general) {
		usort($general, fn($a, $b) => strcmp($a['title'], $b['title']));
		$groups[] = [
			'audience' => '',
			'title' => wrap_text('General'),
			'texts' => $general,
		];
	}
	return $groups;
}

/**
 * all help texts, best variant per identifier (legacy flat list)
 *
 * @param array $files
 * @return array
 */
function mf_default_help_all($files) {
	$data = [];
	foreach ($files as $identifier => $variants) {
		$variant = mf_default_help_pick_variant($variants);
		if (!$variant) continue;
		$data[] = mf_default_help_content($variant);
	}
	foreach ($data as $index => $text) {
		if ($text['language'] !== wrap_setting('lang'))
			$data[$index]['foreign_language'] = true;
	}
	return $data;
}

/**
 * pick one help text variant by identifier, optionally restricted to a package
 *
 * @param array $files from mf_default_help_files()
 * @param string $identifier
 * @param string|null $package (optional)
 * @return array|null
 */
function mf_default_help_pick($files, $identifier, $package = NULL) {
	if (!array_key_exists($identifier, $files)) return NULL;
	$variant = mf_default_help_pick_variant($files[$identifier], $package);
	if (!$variant) return NULL;
	return mf_default_help_content($variant);
}

/**
 * pick best variant from a list, optionally for one package
 *
 * @param array $variants
 * @param string|null $package (optional)
 * @return array|null
 */
function mf_default_help_pick_variant($variants, $package = NULL) {
	$best = NULL;
	foreach ($variants as $variant) {
		if ($package AND $variant['package'] !== $package) continue;
		if (!$best OR mf_default_help_better($variant, $best))
			$best = $variant;
	}
	return $best;
}

/**
 * check if help file is more important than existing in list
 * i. e. language better, package not default
 *
 * @param array $new
 * @param array $existing
 * @return bool true = better
 */
function mf_default_help_better($new, $existing) {
	// check language
	if ($new['language'] === wrap_setting('lang')) return true;
	if ($new['language'] === '' AND $existing['language'] !== wrap_setting('lang')) return true;
	// check package
	if ($existing['package'] === 'default' AND $new['package'] !== 'default') return true;
	return false;
}

/**
 * get content of help file, set title
 *
 * @param array $file
 * @return array
 */
function mf_default_help_content($file) {
	$raw = file_get_contents($file['filename']);
	$file['audience'] = mf_default_help_audience($raw);
	$file['text'] = preg_replace('/<!--[\s\S]*?-->/', '', $raw);
	$file['text'] = preg_replace('/%%%(.*?)%%%/s', '%%% explain $1%%%', $file['text']);
	$file['text'] = mf_default_help_links($file['text'], $file['package']);

	if ($file['type'] === 'md') {
		preg_match('/# (.+)/', $file['text'], $matches);
		if (!empty($matches[1])) $file['title'] = $matches[1];
	}
	return $file;
}

/**
 * help identifier from a file or link name (lowercase, no extension)
 *
 * @param string $name e.g. "Format.md", "Page Elements", "format"
 * @return string e.g. "format", "page-elements"
 */
function mf_default_help_identifier($name) {
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
 * @param string|null $package package of the source file (optional)
 * @return string
 */
function mf_default_help_links($text, $package = NULL) {
	static $files = null;
	if ($files === null)
		$files = mf_default_help_files();

	return preg_replace_callback(
		'/\[([^\]]+)\]\(([^)\s]+(?:\s+"[^"]*")?)\)/',
		function ($match) use ($files, $package) {
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
			$identifier = mf_default_help_identifier($url);
			if (!$identifier || !array_key_exists($identifier, $files)) return $match[0];

			$variant = mf_default_help_pick_variant($files[$identifier], $package)
				?? mf_default_help_pick_variant($files[$identifier]);
			if (!$variant) return $match[0];

			$path = wrap_path('default_help', [$variant['package'], $identifier]);
			if (!$path) return $match[0];

			$output = sprintf('[%s](%s%s', $link_text, $path, $anchor);
			if ($title) $output .= ' "'.$title.'"';
			$output .= ')';
			return $output;
		},
		$text
	);
}

/**
 * audience list from a help file header Variables block
 *
 * @param string $content raw file contents
 * @return array audience identifiers
 */
function mf_default_help_audience($content) {
	wrap_include('file', 'zzwrap');
	$variables = wrap_file_header_variables($content);
	if (empty($variables['audience'])) return [];

	$allowed = mf_default_help_audiences();
	$audiences = [];
	foreach (preg_split('/\s*,\s*/', $variables['audience']) as $audience) {
		$audience = trim($audience);
		if (!$audience) continue;
		if (!in_array($audience, $allowed, true)) {
			wrap_error(sprintf(
				'Unknown help audience `%s`, allowed: %s.',
				$audience,
				implode(', ', $allowed)
			), E_USER_NOTICE);
			continue;
		}
		$audiences[] = $audience;
	}
	return array_values(array_unique($audiences));
}

/**
 * configured audience identifiers from help.cfg
 *
 * @return array
 */
function mf_default_help_audiences() {
	static $audiences = null;
	if ($audiences !== null) return $audiences;

	$cfg = wrap_cfg_files('help');
	$audiences = $cfg['audience']['default'] ?? [];
	if (!is_array($audiences)) $audiences = [$audiences];
	return $audiences;
}

/**
 * section title for one audience
 *
 * @param string $audience
 * @return string
 */
function mf_default_help_audience_title($audience) {
	$cfg = wrap_cfg_files('help', ['translate' => true]);
	$key = 'audience.'.$audience;
	if (!empty($cfg[$key]['description']))
		return $cfg[$key]['description'];
	return $audience;
}

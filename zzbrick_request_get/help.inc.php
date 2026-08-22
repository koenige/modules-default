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
	if (count($params) === 1 && strstr($params[0], '/'))
		$params = explode('/', $params[0]);
	if (count($params) === 2)
		return mf_default_help_pick($files, $params[1], $params[0]) ?? [];
	if (count($params) === 1)
		return mf_default_help_pick($files, $params[0]) ?? [];
	return mf_default_help_all($files);
}

/**
 * get all help files from help/index.json per package
 *
 * @return array identifier => variants
 */
function mf_default_help_files() {
	static $files = [];
	if ($files) return $files;

	$index_files = wrap_collect_files('help/index.json');
	foreach ($index_files as $package => $index_path) {
		$data = json_decode(file_get_contents($index_path), true);
		if (!is_array($data)) {
			wrap_error(['Help index %s is not readable.', ['values' => [$index_path]]]);
			continue;
		}
		$folder = wrap_package_folder($package);
		foreach ($data as $identifier => $variants) {
			foreach ($variants as $variant) {
				if (!empty($variant['filename']))
					$variant['filename'] = $folder.'/'.$variant['filename'];
				$files[$identifier][] = $variant;
			}
		}
	}
	return $files;
}

/**
 * collect help/*.{txt,md} from modules and custom
 *
 * @param string $package
 * @return array
 */
function mf_default_help_collect($package) {
	$files = wrap_collect_files('help/*.{txt,md}', $package);

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
		$package = substr($package, 0, strrpos($package, '/'));
		$filename_local = substr($file, strlen(wrap_package_folder($package)) + 1);
		$raw = file_get_contents($file);
		if ($raw === false) {
			wrap_error(['Help file %s is not readable.', ['values' => [$file]]]);
			continue;
		}
		$metadata = mf_default_help_metadata($raw, $extension, $title);
		$data[$basename][] = [
			'title' => $metadata['title'],
			'language' => $lang ?? 'en',
			'package' => $package,
			'filename' => $filename_local,
			'identifier' => $basename,
			'type' => $extension,
			'audience' => $metadata['audience'],
		];
	}

	wrap_include('zzbrick_request_get/tables', 'default');
	foreach (mf_default_help_collect_parameters($package) as $identifier => $variants)
		$data[$identifier] = $variants;

	return $data ?? [];
}

/**
 * parameter reference index entries from zzbrick_tables scan
 *
 * @param string $package
 * @return array identifier => variants
 */
function mf_default_help_collect_parameters($package) {
	$tables = mf_default_tables_collect($package);
	if (!$tables) return [];

	$entries = [];
	foreach ($tables as $table => $meta) {
		if (empty($meta['parameter_field'])) continue;
		
		$identifier = $table.'-parameters';
		$entries[$identifier][] = [
			'title' => wrap_text('%s parameters', ['values' => [$meta['title']], 'lang' => 'en']),
			'language' => 'en',
			'package' => $package,
			'identifier' => $identifier,
			'type' => 'parameters',
			'audience' => $meta['audience'] ?? ['editor'],
			'table' => $table,
			'parameter_field' => $meta['parameter_field'],
		];
		foreach (mf_default_help_po_languages('default') as $language) {
			$format = mf_default_help_po_msgstr('default', $language, '%s parameters');
			if (!$format) continue;
			$translated_title = mf_default_help_po_msgstr($package, $language, $meta['title']);
			if (!$translated_title) $translated_title = $meta['title'];
			$entries[$identifier][] = [
				'title' => sprintf($format, $translated_title),
				'language' => $language,
				'package' => $package,
				'identifier' => $identifier,
				'type' => 'parameters',
				'audience' => $meta['audience'] ?? ['editor'],
				'table' => $table,
				'parameter_field' => $meta['parameter_field'],
			];
		}
	}
	return $entries;
}

/**
 * language codes with a non-admin .po file for one package
 *
 * @param string $package
 * @return array
 */
function mf_default_help_po_languages($package) {
	$basename = wrap_text_language_basename($package);
	$folder = wrap_text_languages_path($package);
	if (!$folder) return [];

	$languages = [];
	foreach (glob($folder.'/'.$basename.'-*.po') ?: [] as $file) {
		if (!preg_match('/^'.preg_quote($basename, '/').'-([a-z]{2})\.po$/', basename($file), $matches))
			continue;
		if ($matches[1] === 'en') continue;
		$languages[] = $matches[1];
	}
	sort($languages);
	return $languages;
}

/**
 * translated msgid from one package .po file
 *
 * @param string $package
 * @param string $language
 * @param string $msgid
 * @return string|null empty if untranslated
 */
function mf_default_help_po_msgstr($package, $language, $msgid) {
	static $cache = [];

	$key = $package.'/'.$language;
	if (!array_key_exists($key, $cache)) {
		$file = sprintf(
			'%s/%s-%s.po',
			wrap_text_languages_path($package),
			wrap_text_language_basename($package),
			$language
		);
		if (!file_exists($file)) {
			$cache[$key] = null;
		} else {
			$cache[$key] = wrap_po_parse($file);
		}
	}
	if (!$cache[$key]) return null;

	$text = $cache[$key];
	foreach (['_global', ''] as $context) {
		if (!empty($text[$context][$msgid])) return $text[$context][$msgid];
	}
	return null;
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
	usort($packages, function ($a, $b) {
		return strcmp($a['name'], $b['name']);
	});
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
		if ($variant['language'] !== wrap_setting('lang'))
			$variant['foreign_language'] = true;
		$data[] = $variant;
	}
	usort($data, function ($a, $b) {
		return strcmp($a['title'], $b['title']);
	});
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
		usort($buckets[$audience], function ($a, $b) {
			return strcmp($a['title'], $b['title']);
		});
		$groups[] = [
			'audience' => $audience,
			'title' => mf_default_help_audience_title($audience),
			'texts' => $buckets[$audience],
		];
	}
	if ($general) {
		usort($general, function ($a, $b) {
			return strcmp($a['title'], $b['title']);
		});
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
	if (($file['type'] ?? '') === 'parameters')
		return mf_default_help_parameters_content($file);

	$raw = file_get_contents($file['filename']);
	$metadata = mf_default_help_metadata($raw, $file['type'], $file['title']);
	$file['title'] = $metadata['title'];
	$file['audience'] = $metadata['audience'];
	$file['text'] = preg_replace('/<!--[\s\S]*?-->/', '', $raw);
	$file['text'] = preg_replace('/%%%(.*?)%%%/s', '%%% explain $1%%%', $file['text']);
	$file['text'] = mf_default_help_links($file['text'], $file['package']);
	return $file;
}

/**
 * title and audience from raw help file contents
 *
 * @param string $raw
 * @param string $type md|txt
 * @param string $title_fallback from filename
 * @return array title, audience
 */
function mf_default_help_metadata($raw, $type, $title_fallback) {
	$title = $title_fallback;
	if ($type === 'md') {
		$text = preg_replace('/<!--[\s\S]*?-->/', '', $raw);
		preg_match('/# (.+)/', $text, $matches);
		if (!empty($matches[1])) $title = $matches[1];
	}
	return [
		'title' => $title,
		'audience' => mf_default_help_audience($raw),
	];
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
 * Cross-package: [Translating Text](zzwrap/Translating Text.md).
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
		'/\[([^\]]+)\]\(([^)]+)\)/',
		function ($match) use ($files, $package) {
			$link_text = $match[1];
			$url = trim($match[2]);
			$title = '';
			if (preg_match('/^(.+?)\s+"([^"]*)"$/', $url, $parts)) {
				$url = trim($parts[1]);
				$title = $parts[2];
			}
			if (preg_match('#^[a-z]+:#i', $url)) return $match[0];
			if (str_starts_with($url, '/') || str_starts_with($url, '#')) return $match[0];

			$anchor = '';
			if (($pos = strpos($url, '#')) !== false) {
				$anchor = substr($url, $pos);
				$url = substr($url, 0, $pos);
			}

			$explicit_package = NULL;
			$target = $url;
			if (strpos($url, '/') !== false) {
				[$explicit_package, $target] = explode('/', $url, 2);
			}
			$identifier = mf_default_help_identifier($target);
			if (!$identifier || !array_key_exists($identifier, $files)) return $match[0];

			if ($explicit_package) {
				$variant = mf_default_help_pick_variant($files[$identifier], $explicit_package);
			} else {
				$variant = mf_default_help_pick_variant($files[$identifier], $package)
					?? mf_default_help_pick_variant($files[$identifier]);
			}
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
	return mf_default_help_audience_list($variables['audience'] ?? null);
}

/**
 * audience list from cfg values or a literal
 *
 * @param mixed $value
 * @return array audience identifiers
 */
function mf_default_help_audience_list($value) {
	$allowed = mf_default_help_audiences();
	$audiences = [];
	foreach (wrap_array_list($value) as $audience) {
		if (!in_array($audience, $allowed, true)) {
			wrap_error(['Unknown help audience `%s`, allowed: %s.', ['values' => [$audience, implode(', ', $allowed)]]], E_USER_NOTICE);
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

/**
 * render parameter reference page from settings.cfg (scope = table)
 *
 * @param array $variant index entry (type parameters)
 * @return array
 */
function mf_default_help_parameters_content($variant) {
	wrap_include('zzbrick_request/modulesettings', 'default');

	$table = $variant['table'];
	$definitions = wrap_cfg_files('settings', ['scope' => $table, 'translate' => true]);
	$full_cfg = wrap_cfg_files('settings', ['translate' => true]);

	$data = [
		'parameter_field' => $variant['parameter_field'],
		'intro' => wrap_text(
			'Enter parameters as <code>key=value</code> pairs.'
		),
		'empty_message' => wrap_text(
			'No parameters are documented for this table in settings.cfg.'
		),
	];
	if (!$definitions) {
		$data['parameters_empty'] = true;
	} else {
		$rows = [];
		foreach ($definitions as $key => $meta) {
			if (!is_array($meta)) continue;
			unset($meta['package']);
			$description = isset($meta['description']) ? (string) $meta['description'] : '';
			if (is_array($description))
				$description = implode(' ', $description);
			$description = mod_default_modulesettings_escape_pct_for_template($description);
			$key_text = mod_default_modulesettings_key_label($key, $definitions);
			$cfg_line = $full_cfg[$key] ?? $meta;
			$private = !empty($cfg_line['private']);
			$type = isset($cfg_line['type']) ? (string) $cfg_line['type'] : '';
			$default_raw = isset($full_cfg[$key])
				? wrap_get_setting_default($key, $full_cfg[$key])
				: wrap_get_setting_default($key, $meta);
			$default_for_view = mod_default_modulesettings_coerce_list_empty($default_raw, $cfg_line);
			$row = [
				'key' => $key_text,
				'description' => $description,
				'type' => $type,
				'deprecated' => !empty($cfg_line['deprecated']),
				'default_display' => mod_default_modulesettings_value_display(
					$default_for_view,
					$private,
					$type
				),
			];
			$example_lines = mod_default_modulesettings_examples_lines($cfg_line);
			if ($example_lines)
				$row['examples'] = $example_lines;
			$enum_lines = mod_default_modulesettings_enum_lines($cfg_line);
			if ($enum_lines)
				$row['enums'] = $enum_lines;
			$rows[] = $row;
		}
		usort($rows, 'mod_default_modulesettings_compare_rows');
		$data['rows'] = $rows;
	}

	$variant['text'] = wrap_template('help-parameters', $data);
	return $variant;
}

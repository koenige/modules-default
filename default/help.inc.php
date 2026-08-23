<?php 

/**
 * default module
 * shared help index and audience helpers
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


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

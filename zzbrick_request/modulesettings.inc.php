<?php 

/**
 * default module
 * list settings from configuration/settings.cfg for one package vs runtime values
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Table of declared settings (current values; defaults when differing)
 *
 * @param array $params a single slug: package folder name
 * @return array|false
 */
function mod_default_modulesettings($params) {
	wrap_access_quit('default_modulesettings');

	if (count($params) !== 1) return false;
	$module = $params[0];
	if (!in_array($module, wrap_setting('modules'))) return false;

	$pkg = wrap_cfg_files('package', ['package' => $module]);
	$module_name = $pkg['about']['name'] ?? $module;

	$definitions = wrap_cfg_files('settings', ['package' => $module]);
	$page = [];
	$data = [];
	if (!$definitions) {
		$data['settings_empty'] = true;
		$page['text'] = wrap_template('modulesettings', $data);
	} else {
		$full_cfg = wrap_cfg_files('settings');
		$rows = [];
		foreach ($definitions as $key => $meta) {
			if (!is_array($meta)) continue;
			unset($meta['package']);
			$description = isset($meta['description']) ? (string) $meta['description'] : '';
			if (is_array($description)) {
				$description = implode(' ', $description);
			}
			$description = mod_default_modulesettings_escape_pct_for_template($description);
			$key_text = mod_default_modulesettings_key_label($key, $definitions);
			$cfg_line = $full_cfg[$key] ?? $meta;
			$private = !empty($cfg_line['private']);
			$type = isset($cfg_line['type']) ? (string) $cfg_line['type'] : '';
			$scopes = mod_default_modulesettings_scope_list($cfg_line);
			$default_raw = isset($full_cfg[$key])
				? wrap_get_setting_default($key, $full_cfg[$key])
				: wrap_get_setting_default($key, $meta);
			$current_raw = wrap_setting($key);
			$default_for_view = mod_default_modulesettings_coerce_list_empty($default_raw, $cfg_line);
			$current_for_view = mod_default_modulesettings_coerce_list_empty($current_raw, $cfg_line);
			$rows[] = [
				'scopes' => $scopes,
				'key' => $key_text,
				'description' => $description,
				'type' => $type,
				'current_display' => mod_default_modulesettings_value_display($current_for_view, $private, $type),
				'default_display' => mod_default_modulesettings_is_overridden($default_for_view, $current_for_view)
					? mod_default_modulesettings_value_display($default_for_view, $private, $type)
					: '',
			];
		}
		$data['sections'] = mod_default_modulesettings_sections_from_rows($rows);

		$page['text'] = wrap_template('modulesettings', $data);
	}

	$page['title'] = wrap_text('Module settings for %s', ['values' => [$module_name]]);
	if ($parent = wrap_path('default_module', $module)) {
		$page['breadcrumbs'][] = ['title' => $module_name, 'url_path' => $parent];
	}
	$page['breadcrumbs'][]['title'] = wrap_text('Module settings');

	return $page;
}

/**
 * Stable sort by setting label
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function mod_default_modulesettings_compare_rows($a, $b) {
	return strcmp((string) $a['key'], (string) $b['key']);
}

/**
 * Normalized list of scope identifiers from a setting definition line
 *
 * @param array $cfg_line merged settings.cfg line
 * @return array list of non-empty strings (may be empty = applies everywhere → Website-only block)
 */
function mod_default_modulesettings_scope_list(array $cfg_line): array {
	if (empty($cfg_line['scope'])) {
		return [];
	}
	$list = $cfg_line['scope'];
	$list = is_array($list) ? $list : [$list];
	$list = array_map('trim', $list);
	$list = array_filter($list, static function ($s) {
		return $s !== '';
	});
	return array_values(array_unique($list));
}

/**
 * Rows with `scopes` → sections: Website (no scope or includes website), then one section per other scope (duplicates kept)
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array{title: string, rows: array<int, mixed>}>
 */
function mod_default_modulesettings_sections_from_rows(array $rows): array {
	$website_rows = [];
	$scope_buckets = [];

	foreach ($rows as $row) {
		$scope_ids = isset($row['scopes']) ? $row['scopes'] : [];
		unset($row['scopes']);

		if ($scope_ids === [] || in_array('website', $scope_ids, true)) {
			$website_rows[] = $row;
		}
		foreach ($scope_ids as $sid) {
			if ($sid === 'website') {
				continue;
			}
			if (!isset($scope_buckets[$sid])) {
				$scope_buckets[$sid] = [];
			}
			$scope_buckets[$sid][] = $row;
		}
	}

	$sections = [];
	if ($website_rows) {
		usort($website_rows, 'mod_default_modulesettings_compare_rows');
		$sections[] = [
			'title' => wrap_text('Website'),
			'rows' => $website_rows,
		];
	}
	ksort($scope_buckets, SORT_STRING);
	foreach ($scope_buckets as $scope_id => $bucket_rows) {
		usort($bucket_rows, 'mod_default_modulesettings_compare_rows');
		$sections[] = [
			'title' => $scope_id,
			'rows' => $bucket_rows,
		];
	}

	return $sections;
}

/**
 * Use friendly label only for `_local` shadow entries
 *
 * @param string $key ini section key
 * @param array $definitions definitions for this package
 * @return string
 */
function mod_default_modulesettings_key_label($key, $definitions) {
	if (!str_ends_with($key, '_local')) {
		return $key;
	}
	$plain = substr($key, 0, -strlen('_local'));
	if (array_key_exists($plain, $definitions)) {
		return sprintf('%s (%s)', $plain, wrap_text('local overlay'));
	}
	return $key;
}

/**
 * escape %%% for template output (same as mod_default_make_deprecations)
 *
 * @param string $text
 * @return string
 */
function mod_default_modulesettings_escape_pct_for_template($text) {
	$text = str_replace('%%%%%%', "%%\u{200B}%%\u{200B}%%", $text);
	$text = str_replace('%%%', "%%\u{200B}%", $text);
	return $text;
}

/**
 * list = 1: empty [] equals “no value” (same as NULL) for display and overridden check
 *
 * @param mixed $value
 * @param array $cfg_line setting definition from merged settings.cfg
 * @return mixed
 */
function mod_default_modulesettings_coerce_list_empty($value, array $cfg_line) {
	if (empty($cfg_line['list'])) {
		return $value;
	}
	if (is_array($value) && $value === []) {
		return null;
	}

	return $value;
}

/**
 * Whether runtime value differs from the resolved default-for-display
 *
 * @param mixed $defaults_value
 * @param mixed $runtime_value
 * @return bool
 */
function mod_default_modulesettings_is_overridden($defaults_value, $runtime_value): bool {
	$a = mod_default_modulesettings_compare_blob($defaults_value);
	$b = mod_default_modulesettings_compare_blob($runtime_value);
	return $a !== $b;
}

/**
 * Normalize for comparison only
 *
 * @param mixed $value
 * @return string
 */
function mod_default_modulesettings_compare_blob($value) {
	if ($value === null) {
		return "\0null";
	}
	if (is_bool($value)) {
		return $value ? "\0true" : "\0false";
	}
	if (is_array($value)) {
		return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
	}
	return (string) $value;
}

/**
 * Format a default or current value for HTML table output
 *
 * @param mixed $value
 * @param bool $private metadata from settings.cfg (`private = 1`): mask non-empty values
 * @param string $setting_type `type` from settings.cfg (e.g. `bool`); only then truthy/falsy → yes/no
 */
function mod_default_modulesettings_value_display($value, $private = false, $setting_type = ''): string {
	if ($private) {
		if ($value === null) {
			return '–';
		}
		if ($value === '' || $value === [] || $value === false) {
			return '';
		}
		return '••••••••';
	}
	if ($setting_type === 'bool') {
		return $value ? wrap_text('yes') : wrap_text('no');
	}
	if ($value === null) {
		return '–';
	}
	if (is_array($value)) {
		$encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($encoded === false) {
			return '–';
		}
		return $encoded;
	}
	return (string) $value;
}

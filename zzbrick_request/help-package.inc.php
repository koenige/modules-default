<?php 

/**
 * default module
 * Help: list of texts per package
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * overview of help texts per package
 *
 * @param array $params
 * @return array|false
 */
function mod_default_help_package($params) {
	if (count($params) !== 1) return false;

	$data = [];
	$data['package'] = $params[0];
	$data['audiences'] = mf_default_help_list_grouped($data['package']);
	if (!$data['audiences']) return false;
	$pkg = wrap_cfg_files('package', ['package' => $data['package'], 'translate' => true]);
	$data['name'] = $pkg['about']['name'] ?? $data['package'];
	$data['tagline'] = trim((string) ($pkg['about']['tagline'] ?? ''));

	$page['extra']['css'][] = 'default/help.css';
	$page['breadcrumbs'][] = ['title' => $data['name']];
	$page['title'] = wrap_text('Help for %s', ['values' => [$data['name']]]);
	$page['text'] = wrap_template('help-package', $data);
	return $page;
}

/**
 * help texts for one package, grouped by audience
 *
 * @param string $package
 * @return array list of ['audience', 'title', 'texts']
 */
function mf_default_help_list_grouped($package) {
	wrap_include('help', 'default');
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

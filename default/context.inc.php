<?php

/**
 * default module
 * form context helpers (context / context_if / zzform_if / context_reversed / zzform_reversed)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * read form context(s) for a subtable type
 *
 * @param array $values
 * @param string $type subtable type, e. g. roles, contactdetails, relations
 * @return string|array|null string or array of context identifiers; null if unset or all invalid
 */
function mf_default_contexts($values, $type) {
	if (!isset($values['context'][$type])) return null;

	$raw = $values['context'][$type];
	$contexts = mf_default_context_list($raw);
	if (!$contexts) return null;

	$valid = [];
	foreach ($contexts as $context) {
		if (mf_default_context_valid($context)) {
			$valid[] = $context;
			continue;
		}
		wrap_error(
			['Unknown form context `%s` for `%s`.', ['values' => [$context, $type]]],
			E_USER_NOTICE
		);
	}
	if (!$valid) return null;
	if (is_array($raw)) return $valid;
	return $valid[0];
}

/**
 * normalize context value to a list of non-empty strings
 *
 * @param mixed $contexts string, list of strings, or empty
 * @return array
 */
function mf_default_context_list($contexts) {
	if ($contexts === null || $contexts === '') return [];
	if (!is_array($contexts)) return [$contexts];
	return array_values(array_filter($contexts, fn($context) => $context !== '' && $context !== null));
}

/**
 * check whether a context identifier is valid
 *
 * Slugs are registered in settings.cfg with scope[] = context.
 * Paths contain / and must exist in the categories tree.
 *
 * @param string $context
 * @return bool
 */
function mf_default_context_valid($context) {
	if (!is_string($context)) return false;
	if ($context === '') return false;
	if (str_contains($context, '/')) {
		return wrap_category_id($context, 'check') ? true : false;
	}
	return array_key_exists($context, mf_default_context_slugs());
}

/**
 * registered context slugs from settings.cfg (scope[] = context)
 *
 * @return array keys are valid slug identifiers
 */
function mf_default_context_slugs() {
	static $slugs = null;
	if ($slugs !== null) return $slugs;

	$slugs = [];
	$cfg = wrap_cfg_files('settings');
	foreach ($cfg as $key => $line) {
		if (empty($line['scope'])) continue;
		$scopes = $line['scope'];
		if (!is_array($scopes)) $scopes = [$scopes];
		if (!in_array('context', $scopes, true)) continue;
		$slugs[$key] = true;
	}
	return $slugs;
}

/**
 * whether category parameters include this context
 *
 * @param array $parameters parsed category parameters
 * @param string|array $contexts one context or list (OR)
 * @return bool
 */
function mf_default_category_context(array $parameters, $contexts) {
	if (empty($parameters['context'])) return false;
	if (!is_array($parameters['context'])) return false;

	foreach (mf_default_context_list($contexts) as $context) {
		if (empty($parameters['context'][$context])) continue;
		if ($parameters['context'][$context].'' === '1') return true;
	}
	return false;
}

/**
 * merge matching context_if[context] blocks into parameters
 *
 * The context_if[$context] block is a partial parameters tree (same shape as on the
 * category row); merged with wrap_array_merge when context[$context]=1.
 *
 * @param array $parameters parsed category parameters
 * @param string|array $contexts one context or list (OR); blocks merged in order
 * @return array
 */
function mf_default_apply_context_if(array $parameters, $contexts) {
	if (empty($parameters['context_if'])) return $parameters;
	if (!is_array($parameters['context_if'])) return $parameters;

	foreach (mf_default_context_list($contexts) as $context) {
		if (!mf_default_category_context($parameters, $context)) continue;
		if (empty($parameters['context_if'][$context])) continue;
		if (!is_array($parameters['context_if'][$context])) continue;
		$parameters = wrap_array_merge($parameters, $parameters['context_if'][$context]);
	}
	return $parameters;
}

/**
 * merge matching zzform_if[context] blocks into parameters[zzform]
 *
 * @param array $parameters parsed category parameters
 * @param string|array $contexts one context or list (OR); blocks merged in order
 * @return array
 */
function mf_default_apply_zzform_if(array $parameters, $contexts) {
	if (empty($parameters['zzform_if'])) return $parameters;
	if (!is_array($parameters['zzform_if'])) return $parameters;

	foreach (mf_default_context_list($contexts) as $context) {
		if (!mf_default_category_context($parameters, $context)) continue;
		if (empty($parameters['zzform_if'][$context])) continue;
		if (!is_array($parameters['zzform_if'][$context])) continue;
		$parameters['zzform'] = wrap_array_merge(
			$parameters['zzform'] ?? [],
			$parameters['zzform_if'][$context]
		);
	}
	return $parameters;
}

/**
 * apply context_reversed[] overrides when context_if[context][reverse_relation]=1
 *
 * Only applies when context[$context]=1 on the same row.
 * Sets $line['reverse']; path replaces $line['path'], other keys replace parameters.
 *
 * @param array $line category row with parameters (and optional path key)
 * @param string|array $contexts one context or list (OR)
 * @return array
 */
function mf_default_apply_context_reversed(array $line, $contexts) {
	if (empty($line['parameters']) || !is_array($line['parameters'])) return $line;

	$reverse = false;
	foreach (mf_default_context_list($contexts) as $context) {
		if (!mf_default_category_context($line['parameters'], $context)) continue;
		if (empty($line['parameters']['context_if'][$context]['reverse_relation'])) continue;
		if ($line['parameters']['context_if'][$context]['reverse_relation'].'' !== '1') continue;
		$reverse = true;
		break;
	}
	if (!$reverse) return $line;

	$line['reverse'] = true;
	if (empty($line['parameters']['context_reversed'])) return $line;
	if (!is_array($line['parameters']['context_reversed'])) return $line;

	foreach ($line['parameters']['context_reversed'] as $key => $value) {
		if ($key === 'path') {
			$line['path'] = $value;
			continue;
		}
		$line['parameters'][$key] = $value;
	}
	return $line;
}

/**
 * merge zzform_reversed[] into parameters[zzform] when reverse relation is active
 *
 * @param array $line category row with parameters (and optional reverse flag)
 * @return array
 */
function mf_default_apply_zzform_reversed(array $line) {
	if (empty($line['reverse'])) return $line;
	if (empty($line['parameters']['zzform_reversed'])) return $line;
	if (!is_array($line['parameters']['zzform_reversed'])) return $line;

	$line['parameters']['zzform'] = wrap_array_merge(
		$line['parameters']['zzform'] ?? [],
		$line['parameters']['zzform_reversed']
	);
	return $line;
}

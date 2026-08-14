<?php

/**
 * default module
 * form context helpers (context / use_for / if / reversed)
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
 * whether category parameters include this context via use_for
 *
 * @param array $parameters parsed category parameters
 * @param string|array $contexts one context or list (OR)
 * @return bool
 */
function mf_default_category_use_for(array $parameters, $contexts) {
	if (empty($parameters['use_for'])) return false;
	if (!is_array($parameters['use_for'])) return false;

	foreach (mf_default_context_list($contexts) as $context) {
		if (empty($parameters['use_for'][$context])) continue;
		if ($parameters['use_for'][$context].'' === '1') return true;
	}
	return false;
}

/**
 * merge matching if[context] blocks into parameters
 *
 * The if[$context] block is a partial parameters tree (same shape as on the
 * category row); merged with wrap_array_merge when use_for[$context]=1.
 *
 * @param array $parameters parsed category parameters
 * @param string|array $contexts one context or list (OR); blocks merged in order
 * @return array
 */
function mf_default_apply_if(array $parameters, $contexts) {
	if (empty($parameters['if'])) return $parameters;
	if (!is_array($parameters['if'])) return $parameters;

	foreach (mf_default_context_list($contexts) as $context) {
		if (!mf_default_category_use_for($parameters, $context)) continue;
		if (empty($parameters['if'][$context])) continue;
		if (!is_array($parameters['if'][$context])) continue;
		$parameters = wrap_array_merge($parameters, $parameters['if'][$context]);
	}
	return $parameters;
}

/**
 * apply reversed[] overrides when if[context][reverse_relation]=1
 *
 * Only applies when use_for[$context]=1 on the same row.
 *
 * @param array $line category row with parameters (and optional path key)
 * @param string|array $contexts one context or list (OR)
 * @return array
 */
function mf_default_apply_reversed(array $line, $contexts) {
	if (empty($line['parameters']) || !is_array($line['parameters'])) return $line;

	$reverse = false;
	foreach (mf_default_context_list($contexts) as $context) {
		if (!mf_default_category_use_for($line['parameters'], $context)) continue;
		if (empty($line['parameters']['if'][$context]['reverse_relation'])) continue;
		if ($line['parameters']['if'][$context]['reverse_relation'].'' !== '1') continue;
		$reverse = true;
		break;
	}
	if (!$reverse) return $line;

	$line['reverse'] = true;
	if (empty($line['parameters']['reversed'])) return $line;
	if (!is_array($line['parameters']['reversed'])) return $line;

	foreach ($line['parameters']['reversed'] as $key => $value) {
		if ($key === 'path') {
			$line['path'] = $value;
			continue;
		}
		$line['parameters'][$key] = $value;
	}
	return $line;
}

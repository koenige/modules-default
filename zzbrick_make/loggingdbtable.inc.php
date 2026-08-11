<?php

/**
 * default module
 * backfill _logging.db_table from stored query text
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 *
 * Variables
 * translate_pot = admin
 */


/**
 * fill empty db_table values in the logging table
 *
 * @param array $params
 * @return array $page
 */
function mod_default_make_loggingdbtable($params) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST'
		AND array_key_exists('loggingdbtable', $_POST)
		AND $_POST['loggingdbtable'] !== '') {
		return mod_default_make_loggingdbtable_background();
	}

	$data = mod_default_make_loggingdbtable_stats();
	$page = [];
	$page['title'] = wrap_text('Logging table names');
	$page['text'] = wrap_template('make-logging-db-table', $data);
	if (isset($_GET['loggingdbtable'])) {
		$page['query_strings'][] = 'loggingdbtable';
	}
	return $page;
}

/**
 * JSON response for track-progress drive mode
 *
 * @return array $page
 */
function mod_default_make_loggingdbtable_background() {
	wrap_setting('cache', false);
	$api = mod_default_make_loggingdbtable_api();
	$status = $api['_api_status'] ?? 200;
	unset($api['_api_status']);
	$page = [];
	$page['content_type'] = 'json';
	$page['text'] = json_encode($api, JSON_UNESCAPED_UNICODE);
	$page['status'] = $status;
	$page['query_strings'] = ['loggingdbtable', 'cursor', 'processed', 'stopped'];
	return $page;
}

/**
 * drive-mode API: init, step, finalize
 *
 * @return array
 */
function mod_default_make_loggingdbtable_api() {
	$bad = function (array $payload, int $status = 400) {
		if (!empty($payload['error']) && empty($payload['summary'])) {
			$payload['summary'] = $payload['error'];
		}
		return array_merge($payload, ['_api_status' => $status]);
	};

	if (!mod_default_make_loggingdbtable_ready()) {
		return $bad([
			'ok' => false,
			'fatal' => true,
			'error' => wrap_text(
				'The logging table has no db_table column yet. Apply the database update first.'
			),
		]);
	}

	$action = $_POST['loggingdbtable'] ?? '';
	$is_init = ($action === 'init');
	$is_finalize = ($action === 'finalize');
	$is_step = !$is_init && !$is_finalize && is_string($action) && preg_match('/^\d+$/', $action);
	if (!$is_init && !$is_step && !$is_finalize) {
		return $bad([
			'ok' => false,
			'fatal' => true,
			'error' => wrap_text('Invalid logging db_table request.'),
		]);
	}

	if ($is_finalize) {
		$processed = (int) ($_POST['processed'] ?? -1);
		$stopped_raw = $_POST['stopped'] ?? '0';
		$stopped = !empty($stopped_raw) && $stopped_raw !== '0'
			&& strtolower((string) $stopped_raw) !== 'false';
		if ($processed < 0) {
			return $bad([
				'ok' => false,
				'fatal' => true,
				'error' => wrap_text('Invalid processed count.'),
			]);
		}
		$remaining = mod_default_make_loggingdbtable_count_null();
		if ($stopped) {
			$summary = wrap_text(
				'Stopped after %d steps. %d logging rows still have an empty db_table.',
				['values' => [$processed, $remaining]]
			);
		} elseif ($remaining) {
			$summary = wrap_text(
				'Finished %d steps. %d logging rows still have an empty db_table (queries without a table name).',
				['values' => [$processed, $remaining]]
			);
		} else {
			$summary = wrap_text('All logging rows with a parseable table name were updated.');
		}
		return [
			'ok' => true,
			'summary' => $summary,
		];
	}

	if ($is_init) {
		$remaining = mod_default_make_loggingdbtable_count_null();
		if (!$remaining) {
			return [
				'ok' => true,
				'total' => 0,
				'message' => wrap_text('No logging rows with an empty db_table were found.'),
			];
		}
		$chunk = mod_default_make_loggingdbtable_chunk_size();
		return [
			'ok' => true,
			'total' => (int) ceil($remaining / $chunk),
		];
	}

	$cursor = (int) preg_replace('/\D/', '', $_POST['cursor'] ?? '0');
	$result = mod_default_make_loggingdbtable_chunk($cursor);
	if ($result === false) {
		return $bad([
			'ok' => false,
			'fatal' => true,
			'error' => wrap_text('Could not read logging rows.'),
		]);
	}

	$message = wrap_text(
		'log_id %d–%d: %d updated, %d without table name.',
		['values' => [
			$result['first_log_id'] ?: $cursor,
			$result['cursor'],
			$result['updated'],
			$result['skipped'],
		]]
	);
	return [
		'ok' => true,
		'message' => $message,
		'is_error' => $result['processed'] > 0 && !$result['updated'] && $result['skipped'],
		'next' => ['cursor' => (string) $result['cursor']],
	];
}

/**
 * page stats for the GET view
 *
 * @return array
 */
function mod_default_make_loggingdbtable_stats() {
	$data = [
		'ready' => mod_default_make_loggingdbtable_ready(),
		'pending' => 0,
		'chunk_size' => mod_default_make_loggingdbtable_chunk_size(),
	];
	if ($data['ready']) {
		$data['pending'] = mod_default_make_loggingdbtable_count_null();
	}
	return $data;
}

/**
 * @return bool
 */
function mod_default_make_loggingdbtable_ready() {
	$columns = wrap_db_columns(wrap_sql_table('zzform_logging'));
	return in_array('db_table', $columns);
}

/**
 * @return int|false
 */
function mod_default_make_loggingdbtable_count_null() {
	$sql = 'SELECT COUNT(*) FROM /*_TABLE zzform_logging _*/ WHERE db_table IS NULL';
	return wrap_db_fetch($sql, '', 'single value');
}

/**
 * @return int
 */
function mod_default_make_loggingdbtable_chunk_size() {
	return 1000;
}

/**
 * process one chunk of logging rows
 *
 * Uses wrap_db_query() directly so updates are not logged again.
 *
 * @param int $cursor last processed log_id
 * @return array|false
 */
function mod_default_make_loggingdbtable_chunk($cursor) {
	$chunk = mod_default_make_loggingdbtable_chunk_size();
	$table = wrap_sql_table('zzform_logging');
	$sql = sprintf(
		'SELECT log_id, query FROM %s WHERE log_id > %d AND db_table IS NULL ORDER BY log_id LIMIT %d',
		$table,
		$cursor,
		$chunk
	);
	$rows = wrap_db_fetch($sql, 'log_id');
	if ($rows === false) {
		return false;
	}
	if (!$rows) {
		return [
			'cursor' => $cursor,
			'first_log_id' => 0,
			'updated' => 0,
			'skipped' => 0,
			'processed' => 0,
		];
	}

	$updated = 0;
	$skipped = 0;
	$first_log_id = 0;
	$last_log_id = $cursor;
	foreach ($rows as $log_id => $row) {
		$log_id = (int) $log_id;
		if (!$first_log_id) {
			$first_log_id = $log_id;
		}
		$last_log_id = $log_id;
		$db_table = wrap_db_log_table($row['query']);
		if (!$db_table) {
			$skipped++;
			continue;
		}
		$update = sprintf(
			'UPDATE %s SET db_table = "%s" WHERE log_id = %d AND db_table IS NULL',
			$table,
			wrap_db_escape($db_table),
			$log_id
		);
		if (!wrap_db_query($update)) {
			return false;
		}
		$updated++;
	}

	return [
		'cursor' => $last_log_id,
		'first_log_id' => $first_log_id,
		'updated' => $updated,
		'skipped' => $skipped,
		'processed' => count($rows),
	];
}

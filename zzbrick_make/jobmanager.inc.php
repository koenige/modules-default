<?php

/**
 * default module
 * manage background jobs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/tournaments
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_make_jobmanager() {
	wrap_package_activate('zzform'); // for CSS
	// get count of jobs
	$data = mod_default_make_jobmanager_count();

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$page['text'] = wrap_template('jobmanager', $data);
		return $page;
	}
	if (!empty($_POST['url']))
		$job_id = mod_default_make_jobmanager_add($_POST);
	wrap_job_debug('START JOBMANAGER job_id '.($job_id ?? ''), $_POST);

	$time = time();
	while ($time + wrap_setting('default_jobs_request_runtime_secs') > time()) {
		$job = mod_default_make_jobmanager_get($job_id ?? 0);
		wrap_job_debug('NEW JOB', $job);
		$job_id = 0; // use job_id just for the first call
		if (!$job) break;
		
		// @todo allow to run a certain number of jobs per category in parallel
		// with wrap_lock(), category, parameters, e. g. `max_requests`

		$started = mod_default_make_jobmanager_start($job);
		if (!$started) break;
		if ($job['postdata']) {
			parse_str($job['postdata'], $job['postdata']);
			unset($job['postdata']['trigger']);
			unset($job['postdata']['url']);
		}

		if (!empty($_SERVER['HTTP_X_TIMEOUT_IGNORE']))
			list($status, $headers, $response)
				= wrap_trigger_protected_url($job['job_url'], $job['username'], 'POST', $job['postdata']);
		else {
			$headers_to_send = [];
			$headers_to_send[] = sprintf('X-Lock-Hash: %s', wrap_lock_hash());
			list($status, $headers, $response)
				= wrap_get_protected_url($job['job_url'], $headers_to_send, 'POST', $job['postdata'], $job['username']);
		}
		if (!in_array($status, [100, 200]))
			wrap_error(sprintf(
				'Job Manager with URL %s failed. (Status: %d, Headers: %s)'
				, $job['job_url'], $status, json_encode($headers)
			), E_USER_NOTICE, ['log_post_data' => false]);

		if (empty($_SERVER['HTTP_X_TIMEOUT_IGNORE'])) {
			$result = mod_default_make_jobmanager_finish($job, $status, $response);
			if ($result) {
				if (!array_key_exists($result, $data)) $data[$result] = 0;
				$data[$result]++;
			}
		}
		if (!empty($_POST['sequential'])) break;
		if (!empty($_POST['single'])) break;
		usleep(wrap_setting('default_jobs_sleep_between_microseconds'));
	}

	$data['results'] = true;
	$data['deleted'] = mod_default_make_jobmanager_delete();
	$data['released'] = mod_default_make_jobmanager_release();
	$page['text'] = wrap_template('jobmanager', $data);
	return $page;
}

/**
 * get number of jobs in different categories and per status
 *
 * @return array
 */
function mod_default_make_jobmanager_count() {
	$sql = 'SELECT CONCAT(category_id, "-", job_status) AS id
			, COUNT(*) AS jobs, job_status, category_id, category
		FROM _jobqueue
		LEFT JOIN categories
			ON _jobqueue.job_category_id = categories.category_id
		WHERE job_status NOT IN ("successful", "not_found")
		GROUP BY category_id, job_status';
	$categories = wrap_db_fetch($sql, 'id');
	if ($categories)
		$categories = wrap_translate($categories, 'categories', 'category_id');
	$data = [];
	foreach ($categories as $category) {
		$id = $category['category_id'];
		$data[$id]['category_id'] = $id;
		$data[$id]['category'] = $category['category'];
		$data[$id][$category['job_status']] = $category['jobs'];
	}
	return $data;
}

/**
 * add a job
 *
 * @param array $data
 * @return int
 */
function mod_default_make_jobmanager_add($data) {
	// allow wait_until to be numeric
	if (!empty($data['wait_until']) AND is_numeric($data['wait_until']))
		$data['wait_until'] = date('Y-m-d H:i:s', time() + $data['wait_until']);
	// check if already a job by this name exists that will be started again
	$sql = 'SELECT job_id
		FROM _jobqueue
		WHERE job_url = "%s"
		AND website_id = %d
		AND (ISNULL(wait_until) OR wait_until <= %s)
		AND job_status IN (%s"not_started", "failed")';
	$sql = sprintf($sql
		, $data['url']
		, wrap_setting('website_id') ?? 1
		, !empty($data['wait_until']) ? sprintf('"%s"', $data['wait_until']) : "NOW()"
		, !empty($data['sequential']) ? '"running", ' : ''
	);
	$job_ids = wrap_db_fetch($sql, 'job_id', 'single value');
	if ($job_ids) {
		// prolong waiting period if new wait_until is given
		if (!empty($data['wait_until']) AND count($job_ids) === 1 AND empty($data['sequential'])) {
			$sql = 'UPDATE _jobqueue
				SET wait_until = "%s", try_no = try_no + %d
				WHERE job_id = %d
				AND NOT ISNULL(wait_until)
				AND wait_until < "%s"';
			$sql = sprintf($sql
				, $data['wait_until']
				, $data['try_no_increase'] ?? 0
				, reset($job_ids)
				, $data['wait_until']
			);
			wrap_db_query($sql);
		}
		return reset($job_ids);
	}
	
	$postdata = $data;
	$remove_keys = [
		'wait_until', 'url', 'try_no_increase', 'job_category_id', 'priority', 'trigger'
	];

	$line = [
		'job_url' => $data['url'],
		'job_category_id' => $data['job_category_id'] ?? NULL,
		'username' => wrap_username(),
		'priority' => $data['priority'] ?? 0,
		'wait_until' => $data['wait_until'] ?? NULL,
		'website_id' => wrap_setting('website_id') ?? 1,
		'lock_hash' => wrap_lock_hash(),
		'postdata' => $postdata ? http_build_query($postdata) : ''
	];
	return zzform_insert('jobqueue', $line, E_USER_NOTICE, ['msg' => 'Job Manager', 'log_post_data' => false]);
}

/**
 * read next job
 *
 * @param int $job_id (optional, immediately call this specific job if available)
 * @return array
 */
function mod_default_make_jobmanager_get($job_id = 0) {
	$sql = 'SELECT job_id, job_url, username, try_no
			, (SELECT COUNT(*) FROM _jobqueue jq
				WHERE jq.job_category_id = _jobqueue.job_category_id
				AND job_status = "running" AND job_id != %d
			) AS running_jobs
			, SUBSTRING_INDEX(SUBSTRING_INDEX(parameters, "max_requests=", -1), "&", 1) AS max_request
			, postdata
		FROM _jobqueue
		LEFT JOIN categories
			ON _jobqueue.job_category_id = categories.category_id
		WHERE job_status IN (%s"not_started", "failed")
		AND (ISNULL(wait_until) OR wait_until <= NOW())
		AND website_id = %d
		%s
		HAVING (running_jobs < max_request OR max_request = "")
		ORDER BY priority ASC, try_no ASC, created ASC
		LIMIT 1';
	$sql = sprintf($sql
		, $job_id
		, !empty($_POST['sequential']) ? '"running", ' : ''
		, wrap_setting('website_id') ?? 1
		, ($job_id ? sprintf('AND job_id = %d', $job_id) : '')
	);
	wrap_job_debug('JOB QUERY '.$sql);
	$job = wrap_db_fetch($sql);
	wrap_job_debug('JOB RESULT', $job);
	if (!$job) return [];
	$job['job_url_raw'] = $job['job_url'];
	$job['job_url'] = wrap_job_url_base($job['job_url']);
	if (!$job['username'])
		$job['username'] = wrap_setting('default_robot_username');
	return $job;
}

/**
 * start a job
 *
 * @param array $job
 * @return bool
 */
function mod_default_make_jobmanager_start($job) {
	wrap_include('syndication', 'zzwrap');
	wrap_job_debug('START NEW JOB', $job);

	// @todo jobs on different servers might collide here if they share the same URLs
	// in that case, add hostname
	$lock_realm = parse_url($job['job_url_raw'], PHP_URL_PATH);
	$lock_realm = 'jobqueue-'.str_replace('/', '-', trim($lock_realm, '/'));

	$locked = wrap_lock($lock_realm, 'wait'); // to avoid race conditions
	if ($locked) {
		wrap_error(sprintf(
			'Job Manager: unable to start job ID %d, jobqueue is locked', $job['job_id']
		), E_USER_NOTICE, ['log_post_data' => false]);
		return false;
	}

	// is an identical job already running?
	$sql = 'SELECT COUNT(*) FROM _jobqueue
	    WHERE job_url = "%s"
	    AND job_status = "running"
	    AND job_id != %d';
	$sql = sprintf($sql, $job['job_url_raw'], $job['job_id']);
	wrap_job_debug('CHECK NEW JOB '.$sql);
	$running = wrap_db_fetch($sql, '', 'single value');
	if ($running) {
		wrap_unlock($lock_realm, 'delete');
		return false;
	}
	
	$sql = 'UPDATE _jobqueue
		SET job_status = "running"
			, started = NOW()
			, finished = NULL
			, try_no = try_no + 1
			, lock_hash = "%s"
		WHERE job_id = %d
		AND job_status != "running"';
	$sql = sprintf($sql
		, wrap_lock_hash()
		, $job['job_id']
	);
	wrap_job_debug('STARTING NEW JOB '.$sql);
	$success = wrap_db_query($sql, E_USER_NOTICE);
	wrap_unlock($lock_realm, 'delete');
	if ($success) return true;
	wrap_error(sprintf(
		'Job Manager: unable to start job ID %d', $job['job_id']
	), E_USER_NOTICE, ['log_post_data' => false]);
	return false;
}

/**
 * finish a job after completion if triggered or called locally
 *
 * @param array $job
 * @param int $status
 * @param array $response
 * @return string
 */
function mod_default_make_jobmanager_finish($job, $status, $response) {
	if (!$job) return ''; // no job, nothing to finish

	if ($status === 200)
		$result = mod_default_make_jobmanager_success($job['job_id']);
	else
		$result = mod_default_make_jobmanager_fail($job, $status, $response);
	return $result;
}

/**
 * successfully finishing a job
 *
 * @param int $job_id
 * @return string
 */
function mod_default_make_jobmanager_success($job_id) {
	$sql = 'UPDATE _jobqueue
		SET job_status = "successful", finished = NOW()
		WHERE job_id = %d';
	$sql = sprintf($sql, $job_id);
	$success = wrap_db_query($sql);
	if ($success) return 'successful';
	wrap_error(sprintf(
		'Job Manager: unable to finish job ID %d successfully', $job_id
	), E_USER_NOTICE, ['log_post_data' => false]);
	return '';
}

/**
 * mark a job as failed
 *
 * @param array $job
 * @param int $status
 * @param array $response
 * @return string
 */
function mod_default_make_jobmanager_fail($job, $status, $response) {
	if ($status === 404) {
		$job_status = 'not_found';
		$error_msg = 'unable to mark job ID %d as not found';
		$wait_until_sql = '';
	} elseif ($job['try_no'] + 1 < wrap_setting('default_jobs_max_tries')) {
		$job_status = 'failed';
		$error_msg = 'unable to delay failed job ID %d';
		$wait_until_sql = sprintf(
			', wait_until = DATE_ADD(NOW(), INTERVAL %s MINUTE)',
			pow(wrap_setting('default_jobs_delay_base_value'), $job['try_no'])
		);
	} else {
		$job_status = 'abandoned';
		$error_msg = 'unable to abandon job ID %d';
		$wait_until_sql = '';
	}

	// do not log overly long responses
	if (is_array($response)) {
		foreach ($response as $key => $value) {
			if (is_array($value)) continue;
			if (mb_strlen($value) <= wrap_setting('default_jobs_response_maxlen')) continue;
			$response[$key] = mb_substr($value, 0, wrap_setting('default_jobs_response_maxlen'));
		}
		$response = json_encode($response);
	} elseif (mb_strlen($response) > wrap_setting('default_jobs_response_maxlen')) {
		$response = mb_substr($response, 0, wrap_setting('default_jobs_response_maxlen'));
	}

	$sql = 'UPDATE _jobqueue
		SET job_status = "%s", finished = NOW()
			, error_msg = CONCAT(IFNULL(error_msg, ""), "Date: ", NOW(), ", URL: %s, Status: %d, Response: %s\n")
			%s
		WHERE job_id = %d';
	$sql = sprintf($sql
		, $job_status
		, $job['job_url']
		, $status
		, wrap_db_escape($response)
		, $wait_until_sql
		, $job['job_id']
	);
	$success = wrap_db_query($sql);
	if ($success) return $job_status;
	$error_msg = sprintf($error_msg, $job['job_id']);
	wrap_error(sprintf(
		'Job Manager: %s', $error_msg
	), E_USER_NOTICE, ['log_post_data' => false]);
	return '';
}

/**
 * delete old entries, older than n hours
 *
 * @return void
 * @return bool
 */
function mod_default_make_jobmanager_delete() {
	$sql = 'SELECT job_id
		FROM _jobqueue
		WHERE job_status IN ("successful", "not_found")
		AND DATE_ADD(finished, INTERVAL %d HOUR) < NOW()';
	$sql = sprintf($sql, wrap_setting('default_jobs_delete_successful_hours'));
	$job_ids = wrap_db_fetch($sql, 'job_id', 'single value');
	if (!$job_ids) return false;
	
	$sql = 'DELETE FROM _jobqueue WHERE job_id IN (%s)';
	$sql = sprintf($sql, implode(',', $job_ids));
	$success = wrap_db_query($sql);
	if ($success) return count($job_ids);
	wrap_error(sprintf(
		'Job Manager: unable to delete jobs ID %s', implode(',', $job_ids)
	), E_USER_NOTICE, ['log_post_data' => false]);
	return false;
}

/**
 * release stuck jobs
 *
 * @return void
 * @return bool
 */
function mod_default_make_jobmanager_release() {
	$sql = 'SELECT job_id, job_url, try_no
		FROM _jobqueue
		WHERE job_status = "running"
		AND DATE_ADD(started, INTERVAL %d MINUTE) < NOW()';
	$sql = sprintf($sql, wrap_setting('default_jobs_resume_running_minutes'));
	$jobs = wrap_db_fetch($sql, 'job_id');
	if (!$jobs) return false;
	
	$successful = 0;
	foreach ($jobs as $job) {
		$success = mod_default_make_jobmanager_fail($job, 403, 'Job was stuck');
		if ($success) $successful++;
	}
	return $successful;
}

/**
 * check if a job might be started
 *
 * @return array
 */
function mod_default_make_jobmanager_check() {
	$sql = 'SELECT job_id
			, IF(ISNULL(wait_until), NULL, IF(wait_until <= NOW(), NULL, 1)) AS wait
			, IF(job_status = "running", IF(DATE_ADD(started, INTERVAL %d MINUTE) > NOW(), 1, NULL), NULL) AS running
			, username, job_status, lock_hash, try_no, job_url
			, CONCAT(SUBSTRING_INDEX(categories.path, "/", -1), "-", job_category_no) AS realm
			, job_url AS job_url_raw
			, postdata
		FROM _jobqueue
		LEFT JOIN categories
			ON _jobqueue.job_category_id = categories.category_id
		WHERE job_url = "%s"
		AND website_id = %d
		AND job_status IN ("not_started", "running", "failed")
		ORDER BY IF(job_status = "not_started", 1, NULL)
			, IF(job_status = "running", 1, NULL)
			, IF(job_status = "failed", 1, NULL)
			, IF(job_status = "abandoned", 1, NULL)
			, wait_until ASC
	';
	$sql = sprintf($sql
		, wrap_setting('default_jobs_resume_running_minutes')
		, wrap_db_escape(wrap_setting('request_uri'))
		, wrap_setting('website_id') ?? 1
	);
	$jobs = wrap_db_fetch($sql, 'job_id');
	
	if (!$jobs) return [];

	// wait for the job to start?	
	foreach ($jobs as $job_id => $job) {
		$jobs[$job_id]['job_url'] = wrap_job_url_base($job['job_url']);
		if ($jobs[$job_id]['postdata'])
			parse_str($jobs[$job_id]['postdata'], $jobs[$job_id]['postdata']);
		else
			$jobs[$job_id]['postdata'] = [];
		if ($job['wait'] AND $job['job_status'] !== 'failed')
			wrap_quit(403, wrap_text('The job should start later.'));
	}

	// is a job already running?
	foreach ($jobs as $job)
		if ($job['running']) {
			if ($job['lock_hash'] !== wrap_lock_hash())
				wrap_quit(403, wrap_text('Another job is running.'));
			else
				return $job;
		}

	// start the job, preferences set by ORDER BY
	$job = reset($jobs);
	$success = mod_default_make_jobmanager_start($job);
	if (!$success) wrap_quit(403, wrap_text('Unable to start job.'));
	wrap_setting('log_username', $job['username']);
	return $job;
}

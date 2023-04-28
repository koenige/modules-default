<?php

/**
 * default module
 * manage background jobs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/tournaments
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2023 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mod_default_make_jobmanager() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$page['text'] = wrap_template('jobmanager');
		return $page;
	}
	if (!empty($_POST['url']))
		$job_id = mod_default_make_jobmanager_add($_POST);

	$data = [];
	$time = time();
	while ($time + wrap_setting('default_jobs_request_runtime_secs') > time()) {
		$job = mod_default_make_jobmanager_get($job_id ?? 0);
		$job_id = 0; // use job_id just for the first call
		if (!$job) break;
		
		// @todo allow to run a certain number of jobs per category in parallel
		// with wrap_lock(), category, parameters, e. g. `max_requests`

		$started = mod_default_make_jobmanager_start($job['job_id']);
		if (!$started) break;

		list($status, $headers, $response)
			= wrap_get_protected_url($job['job_url'], [], 'POST', [], $job['username']);
		if ($status === 200) {
			$result = mod_default_make_jobmanager_success($job['job_id']);
			if ($result) {
				if (!array_key_exists('success', $data)) $data['success'] = 0;
				$data['success']++;
			}
		} elseif ($job['try_no'] + 1 < wrap_setting('default_jobs_max_tries')) {
			$result = mod_default_make_jobmanager_fail($job, $status, $response);
			if ($result) {
				if (!array_key_exists('fail', $data)) $data['fail'] = 0;
				$data['fail']++;
			}
		} else {
			$result = mod_default_make_jobmanager_abandon($job, $status, $response);
			if ($result) {
				if (!array_key_exists('abandon', $data)) $data['abandon'] = 0;
				$data['abandon']++;
			}
		}
	}

	$data['results'] = true;
	$data['delete'] = mod_default_make_jobmanager_delete();
	$page['text'] = wrap_template('jobmanager', $data);
	return $page;
}

/**
 * add a job
 *
 * @param array $data
 * @return int
 */
function mod_default_make_jobmanager_add($data) {
	$values = [];
	$values['action'] = 'insert';
	$values['ids'] = ['job_category_id'];
	$values['POST']['job_url'] = $data['url'];
	$values['POST']['job_category_id'] = $data['job_category_id'] ?? NULL;
	$values['POST']['username'] = wrap_username();
	$values['POST']['priority'] = $data['priority'] ?? 0;
	$values['POST']['wait_until'] = $data['wait_until'] ?? NULL;
	$ops = zzform_multi('jobqueue', $values);
	if (!empty($ops['id'])) return $ops['id'];
	wrap_error(sprintf('Job Manager: unable to add job with URL %s (Error: %s)', $data['url'], json_encode($ops['error'])));
	return 0;
}

/**
 * read next job
 *
 * @param int $job_id (optional, immediately call this specific job if available)
 * @return array
 */
function mod_default_make_jobmanager_get($job_id = 0) {
	$sql = 'SELECT job_id, job_url, username, try_no
		FROM _jobqueue
		WHERE job_status IN ("not_started", "failed")
		AND (ISNULL(wait_until) OR wait_until < NOW())
		%s
		ORDER BY priority ASC, try_no ASC, created ASC
		LIMIT 1';
	$sql = sprintf($sql, ($job_id ? sprintf('AND job_id = %d', $job_id) : ''));
	$job = wrap_db_fetch($sql);
	if (!$job) return [];
	$job['job_url'] = wrap_job_url_base($job['job_url']);
	if (!$job['username'])
		$job['username'] = wrap_setting('default_robot_username');
	return $job;
}

/**
 * start a job
 *
 * @param int $job_id
 * @return bool
 */
function mod_default_make_jobmanager_start($job_id) {
	$sql = 'UPDATE _jobqueue
		SET job_status = "running", started = NOW(), finished = NULL, try_no = try_no + 1
		WHERE job_id = %d
		AND job_status != "running"';
	$sql = sprintf($sql, $job_id);
	$success = wrap_db_query($sql);
	if ($success) return true;
	wrap_error(sprintf('Job Manager: unable to start job ID %d', $job_id));
	return false;
}

/**
 * successfully finishing a job
 *
 * @param int $job_id
 * @return bool
 */
function mod_default_make_jobmanager_success($job_id) {
	$sql = 'UPDATE _jobqueue
		SET job_status = "successful", finished = NOW()
		WHERE job_id = %d';
	$sql = sprintf($sql, $job_id);
	$success = wrap_db_query($sql);
	if ($success) return true;
	wrap_error(sprintf('Job Manager: unable to finish job ID %d successfully', $job_id));
	return false;
}

/**
 * mark a job as failed
 *
 * @param array $job
 * @param int $status
 * @param array $response
 * @return bool
 */
function mod_default_make_jobmanager_fail($job, $status, $response) {
	$sql = 'UPDATE _jobqueue
		SET job_status = "failed", finished = NOW()
			, error_msg = CONCAT(IFNULL(error_msg, ""), "Date: ", NOW(), ", URL: %s, Status: %d, Response: %s\n")
			, wait_until = DATE_ADD(NOW(), INTERVAL %s MINUTE)
		WHERE job_id = %d';
	$sql = sprintf($sql
		, $job['job_url'], $status, json_encode($response)
		, pow(wrap_setting('default_jobs_delay_base_value'), $job['try_no'])
		, $job['job_id']
	);
	$success = wrap_db_query($sql);
	if ($success) return true;
	wrap_error(sprintf('Job Manager: unable to delay failed job ID %d', $job_id));
	return false;
}

/**
 * abandon a failed job
 *
 * @param array $job
 * @param int $status
 * @param array $response
 * @return bool
 */
function mod_default_make_jobmanager_abandon($job, $status, $response) {
	$sql = 'UPDATE _jobqueue
		SET job_status = "abandoned", finished = NOW()
			, error_msg = CONCAT(IFNULL(error_msg, ""), "Date: ", NOW(), ", URL: %s, Status: %d, Response: %s\n")
		WHERE job_id = %d';
	$sql = sprintf($sql
		, $job['job_url'], $status, json_encode($response)
		, $job['job_id']
	);
	$success = wrap_db_query($sql);
	if ($success) return true;
	wrap_error(sprintf('Job Manager: unable to abandon job ID %d', $job_id));
	return false;
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
		WHERE job_status = "successful"
		AND DATE_ADD(finished, INTERVAL %d HOUR) < NOW()';
	$sql = sprintf($sql, wrap_setting('default_jobs_delete_successful_hours'));
	$job_ids = wrap_db_fetch($sql, 'job_id', 'single value');
	if (!$job_ids) return false;
	
	$sql = 'DELETE FROM _jobqueue WHERE job_id IN (%s)';
	$sql = sprintf($sql, implode(',', $job_ids));
	$success = wrap_db_query($sql);
	if ($success) return count($job_ids);
	wrap_error(sprintf('Job Manager: unable to delete jobs ID %s', implode(',', $job_ids)));
	return false;
}

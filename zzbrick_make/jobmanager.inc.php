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
	wrap_package_activate('zzform'); // for CSS
	// get count of jobs
	$data = mod_default_make_jobmanager_count();

	$data['jobqueue_path'] = wrap_path('default_tables', 'jobqueue');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$page['text'] = wrap_template('jobmanager', $data);
		return $page;
	}
	if (!empty($_POST['url']))
		$job_id = mod_default_make_jobmanager_add($_POST);

	$time = time();
	while ($time + wrap_setting('default_jobs_request_runtime_secs') > time()) {
		$job = mod_default_make_jobmanager_get($job_id ?? 0);
		$job_id = 0; // use job_id just for the first call
		if (!$job) break;
		
		// @todo allow to run a certain number of jobs per category in parallel
		// with wrap_lock(), category, parameters, e. g. `max_requests`

		$started = mod_default_make_jobmanager_start($job);
		if (!$started) break;

		if (!empty($_SERVER['HTTP_X_TIMEOUT_IGNORE']))
			list($status, $headers, $response)
				= wrap_trigger_protected_url($job['job_url'], $job['username']);
		else
			list($status, $headers, $response)
				= wrap_get_protected_url($job['job_url'], [], 'POST', [], $job['username']);
		if (!in_array($status, [100, 200]))
			wrap_error(sprintf('Job Manager with URL %s failed. (Status: %d, Headers: %s)', $job['job_url'], $status, json_encode($headers)));

		if (empty($_SERVER['HTTP_X_TIMEOUT_IGNORE'])) {
			$result = mod_default_make_jobmanager_finish($job, $status, $response);
			if ($result) {
				if (!array_key_exists($result, $data)) $data[$result] = 0;
				$data[$result]++;
			}
		}
		usleep(wrap_setting('default_jobs_sleep_between_microseconds'));
	}

	$data['results'] = true;
	$data['delete'] = mod_default_make_jobmanager_delete();
	$data['recover'] = mod_default_make_jobmanager_release();
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
		WHERE job_status != "successful"
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
	// check if already a job by this name exists that will be started again
	$sql = 'SELECT job_id
		FROM _jobqueue
		WHERE job_url = "%s"
		AND website_id = %d
		AND (ISNULL(wait_until) OR wait_until <= %s)
		AND job_status IN ("not_started", "failed")';
	$sql = sprintf($sql
		, $data['url']
		, wrap_setting('website_id') ?? 1
		, !empty($data['wait_until']) ? sprintf('"%s"', $data['wait_until']) : "NOW()"
	);
	$job_id = wrap_db_fetch($sql, '', 'single value');
	if ($job_id) return $job_id;

	$values = [];
	$values['action'] = 'insert';
	$values['ids'] = ['job_category_id', 'website_id'];
	$values['POST']['job_url'] = $data['url'];
	$values['POST']['job_category_id'] = $data['job_category_id'] ?? NULL;
	$values['POST']['username'] = wrap_username();
	$values['POST']['priority'] = $data['priority'] ?? 0;
	$values['POST']['wait_until'] = $data['wait_until'] ?? NULL;
	$values['POST']['website_id'] = wrap_setting('website_id') ?? 1;
	$values['POST']['lock_hash'] = wrap_lock_hash();
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
			, (SELECT COUNT(*) FROM _jobqueue jq
				WHERE jq.job_category_id = _jobqueue.job_category_id
				AND job_status = "running"
			) AS running_jobs
			, SUBSTRING_INDEX(SUBSTRING_INDEX(parameters, "max_requests=", -1), "&", 1) AS max_request
		FROM _jobqueue
		LEFT JOIN categories
			ON _jobqueue.job_category_id = categories.category_id
		WHERE job_status IN ("not_started", "failed")
		AND (ISNULL(wait_until) OR wait_until < NOW())
		AND website_id = %d
		%s
		HAVING (running_jobs < max_request OR max_request = "")
		ORDER BY priority ASC, try_no ASC, created ASC
		LIMIT 1';
	$sql = sprintf($sql
		, wrap_setting('website_id') ?? 1
		, ($job_id ? sprintf('AND job_id = %d', $job_id) : '')
	);
	$job = wrap_db_fetch($sql);
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
	require_once wrap_setting('core').'/syndication.inc.php';

	$locked = wrap_lock('jobqueue', 'wait'); // to avoid race conditions
	if ($locked) return false;

	// is an identical job already running?
	$sql = 'SELECT COUNT(*) FROM _jobqueue
	    WHERE job_url = "%s"
	    AND job_status = "running"';
	$sql = sprintf($sql, $job['job_url_raw']);
	$running = wrap_db_fetch($sql, '', 'single value');
	if ($running) {
		wrap_unlock('jobqueue');
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
	$success = wrap_db_query($sql);
	wrap_unlock('jobqueue', 'delete');
	if ($success) return true;
	wrap_error(sprintf('Job Manager: unable to start job ID %d', $job_id));
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

	if ($status === 200) {
		$result = mod_default_make_jobmanager_success($job['job_id']);
		if ($result) return 'success';
	} elseif ($job['try_no'] + 1 < wrap_setting('default_jobs_max_tries')) {
		$result = mod_default_make_jobmanager_fail($job, $status, $response);
		if ($result) return 'fail';
	} else {
		$result = mod_default_make_jobmanager_abandon($job, $status, $response);
		if ($result) return 'abandon';
	}
	return '';
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
		, $job['job_url']
		, $status
		, wrap_db_escape(is_array($response) ? json_encode($response) : $response)
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

/**
 * release stuck jobs
 *
 * @return void
 * @return bool
 */
function mod_default_make_jobmanager_release() {
	$sql = 'SELECT job_id
		FROM _jobqueue
		WHERE job_status = "running"
		AND DATE_ADD(started, INTERVAL %d MINUTE) < NOW()';
	$sql = sprintf($sql, wrap_setting('default_jobs_resume_running_minutes'));
	$job_ids = wrap_db_fetch($sql, 'job_id', 'single value');
	if (!$job_ids) return false;

	$sql = 'UPDATE _jobqueue SET job_status = "failed" WHERE job_id IN (%s) AND job_status = "running"';
	$sql = sprintf($sql, implode(',', $job_ids));
	$success = wrap_db_query($sql);
	if ($success) return count($job_ids);
	wrap_error(sprintf('Job Manager: unable to release jobs ID %s', implode(',', $job_ids)));
	return false;
}

/**
 * check if a job might be started
 *
 * @return array
 */
function mod_default_make_jobmanager_check() {
	$sql = 'SELECT job_id
			, IF(ISNULL(wait_until), NULL, IF(wait_until < NOW(), NULL, 1)) AS wait
			, IF(job_status = "running", IF(DATE_ADD(started, INTERVAL %d MINUTE) > NOW(), 1, NULL), NULL) AS running
			, username, job_status, lock_hash, try_no, job_url
			, CONCAT(SUBSTRING_INDEX(categories.path, "/", -1), "-", job_category_no) AS realm
			, job_url AS job_url_raw
		FROM _jobqueue
		LEFT JOIN categories
			ON _jobqueue.job_category_id = categories.category_id
		WHERE job_url = "%s"
		AND website_id = %d
		AND job_status != "successful"
		ORDER BY IF(job_status = "not_started", 1, NULL)
			, IF(job_status = "running", 1, NULL)
			, IF(job_status = "failed", 1, NULL)
			, IF(job_status = "abandoned", 1, NULL)
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
		if ($job['wait']) wrap_quit(403, wrap_text('The job should start later.'));
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

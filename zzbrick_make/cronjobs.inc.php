<?php

/**
 * default module
 * Jobs: running cron jobs via the database
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2013-2016, 2018, 2020-2021, 2023, 2025 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Jobs: running cron jobs via the database
 *
 * @param array $params -
 * @return array $page
 */
function mod_default_make_cronjobs($params) {
	// @todo do not do anything if this is not called via POST
	// then remove cache directive
	wrap_setting('cache', false);

	// MySQL sees monday as 0, sunday as 6
	// Cron sees monday as 1, sunday as 0 or 7
	// @todo implement */5 for minutes = every 5 minutes
	// @todo implement set 2,3 for weekdays
	// @todo implement 1-5 for a range of days or weekdays
	$sql = 'SELECT cronjob_id, url
		FROM _cronjobs
		WHERE active = "yes"
		AND (ISNULL(job_minute) OR FIND_IN_SET(MINUTE(NOW()), job_minute))
		AND (ISNULL(job_hour) OR FIND_IN_SET(HOUR(NOW()), job_hour))
		AND (ISNULL(job_day) OR FIND_IN_SET(DAY(NOW()), job_day))
		AND (ISNULL(job_month) OR FIND_IN_SET(MONTH(NOW()), job_month))
		AND (ISNULL(job_weekday) OR job_weekday = WEEKDAY(NOW()) + 1 OR job_weekday = WEEKDAY(NOW()) - 6)
		ORDER BY IF(ISNULL(sequence), 1, 0), sequence, cronjob, url';
	$jobs = wrap_db_fetch($sql, '_dummy_', 'key/value');
	
	if (!$jobs) {
		$page['text'] = wrap_text('No active jobs found.');
	} else {
		foreach ($jobs as $job) {
			$page = wrap_trigger_protected_url($job, wrap_setting('default_robot_username'));
			// just wait a few seconds until last job started
			sleep(wrap_setting('default_cronjobs_wait_seconds'));
		}
	}
	wrap_setting_write('default_cronjobs_last_run', date('Y-m-d H:i:s'));
	
    return $page;
}

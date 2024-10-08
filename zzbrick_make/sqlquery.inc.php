<?php 

/**
 * default module
 * make an SQL query
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2010, 2013-2024 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * change the database with a custom SQL query which is logged
 *
 * @param array $params
 * @return array $page
 */
function mod_default_make_sqlquery($params) {
	// zz_log_sql()
	wrap_include('database', 'zzform');
	
	wrap_setting('log_username_default', 'Maintenance robot 812');

	$result = [];
	$sql = $_POST['sqlquery'] ?? '';
	if ($sql) {
		$statement = wrap_sql_statement($sql);

		if (in_array($statement, [
			'INSERT', 'UPDATE', 'DELETE', 'CREATE TABLE', 'ALTER TABLE', 'CREATE VIEW',
			'ALTER VIEW', 'SET'
		])) {
			$success = wrap_db_query($sql, 0);
			if ($success AND in_array($statement, ['INSERT', 'UPDATE', 'DELETE']) AND !$success['rows']) {
				$result['action_nothing'] = true;
			} elseif ($success) {
				zz_log_sql($sql, '', $success['id'] ?? NULL);
				$result['action'] = wrap_text(ucfirst(strtolower($statement)));
			} else {
				$warnings = wrap_db_warnings('list');
				if ($warnings) {
					$result['error_db_msg'] = $warnings[0]['Message'];
					$result['error_db_errno'] = $warnings[0]['Code'];
				} else {
					$result['error_db_msg'] = wrap_text('Unknown error.');
				}
			}
			$result['change'] = true;
		} else {
			$result['not_supported'] = true;
			$result['token'] = wrap_html_escape($statement);
		}
			
		$result['sql'] = zz_maintenance_sql($sql);
		$result['form_sql'] = str_replace('%%%', '%&shy;%&shy;%', wrap_html_escape($sql));
	}

	$page['title'] = wrap_text('SQL Query');
	$page['breadcrumbs'][]['title'] = wrap_text('SQL Query');
	$page['text'] = wrap_template('sqlquery', $result);
	return $page;
}

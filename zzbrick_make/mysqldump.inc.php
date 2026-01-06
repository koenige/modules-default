<?php

/**
 * default module
 * mysql database dumps
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2011, 2013-2017, 2020-2023, 2025-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Dumps mySQL data and structure
 *
 * @return array $page
 */
function mod_default_make_mysqldump() {
	$page['query_strings'] = ['category', 'db'];
	
	$sql = 'SELECT event_id, event, event_abbr, identifier
			, category_id, category, path, property
		FROM events_categories
		LEFT JOIN categories USING (category_id)
		LEFT JOIN events USING (event_id)
		WHERE main_category_id = /*_ID categories projects/development _*/
		ORDER BY events.identifier';
	$websites = wrap_db_fetch($sql, ['event_id', 'category_id']);
	$data = [];
	foreach ($websites as $event_id => $website) {
		if (!array_key_exists(wrap_category_id('projects/development/database-name'), $website)) {
			continue;
		}
		if (!array_key_exists(wrap_category_id('projects/development/local-folder'), $website)) {
			continue;
		}
		$database_category = $website[wrap_category_id('projects/development/database-name')];
		if (!empty($_GET['db']) AND $_GET['db'] !== $database_category['property']) continue;
		$data[$event_id] = [
			'event_id' => $event_id,
			'event' => $database_category['event'],
			'event_abbr' => $database_category['event_abbr'],
			'identifier' => $database_category['identifier'],
			'database_name' => $database_category['property'],
			'local_folder' => $website[wrap_category_id('projects/development/local-folder')]['property'] ?? ''
		];
	}
	if (!$data) {
		$data['no_websites_found'] = true;
		$page['text'] =  wrap_template('mysqldump', $data);
		return $page;
	}
	$data = wrap_translate($data, 'events');

	if (count($data) > 1) {
		$data['overview'] = true;
		$page['text'] = wrap_template('mysqldump', $data);
		return $page;
	}
	
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$data['website'] = true;
		$page['text'] = wrap_template('mysqldump', $data);
		return $page;
	}

	$credentials = file_get_contents(wrap_setting('local_pwd'));
	$credentials = json_decode($credentials, true);
	$base_folder = dirname(dirname($_SERVER['DOCUMENT_ROOT'])); // remove [host]/www
	$datafile = sprintf('%s/%%s/docs/sql/%%s-data.sql', $base_folder);
	$mysqldump_structure = sprintf(
		'/opt/homebrew/bin/mysqldump -u%s -p%s --routines --no-data --skip-dump-date --databases %%s > %s/%%s/docs/sql/%%s-structure.sql'
		, $credentials['db_user'], $credentials['db_pwd'], $base_folder
	);
	$mysqldump_structure_sed = sprintf(
		"sed -i '' 's/ AUTO_INCREMENT=[0-9]*//g' %s/%%s/docs/sql/%%s-structure.sql"
		, $base_folder
	);
	$mysqldump_data = sprintf(
		'/opt/homebrew/bin/mysqldump -u%s -p%s --skip-dump-date --databases %%s > %s'
		, $credentials['db_user'], $credentials['db_pwd'], $datafile
	);

	if (empty($_GET['category']) OR $_GET['category'] === 'both') {
		$categories[] = 'structure';
		$categories[] = 'data';
	} elseif (in_array($_GET['category'], ['structure', 'data'])) {
		$categories[] = $_GET['category'];
	}

	foreach ($data as $event_id => $website) {
		if (in_array('structure', $categories)) {
			$command = sprintf($mysqldump_structure, $website['database_name'], $website['local_folder'], $website['database_name']);
			exec($command, $output, $return_var);
			if (!$return_var) {
				$data[$event_id]['dump_structure'] = true;
				$command = sprintf($mysqldump_structure_sed, $website['local_folder'], $website['database_name']);
				exec($command, $output, $return_var);
				if (!$return_var)
					$data[$event_id]['increments_successful'] = true;
			} else {
				$data[$event_id]['dump_structure_return'] = json_encode($return_var);
				$data[$event_id]['dump_structure_command'] = $command;
			}
		}
		if (in_array('data', $categories)) {
			$command = sprintf($mysqldump_data, $website['database_name'], $website['local_folder'], $website['database_name']);
			exec($command, $output, $return_var);
			if (!$return_var) {
				$data[$event_id]['dump_data'] = true;
			} else {
				$data[$event_id]['dump_data_return'] = json_encode($return_var);
				$data[$event_id]['dump_data_command'] = $command;
			}
			$filename = sprintf($datafile, $website['local_folder'], $website['database_name']);
			if (file_exists($filename.'.gz')) {
				unlink($filename.'.gz');
			}
			system('gzip '.$filename, $return_var);
			if (!$return_var AND file_exists($filename)) {
				unlink($filename);
			}
		}
	}
	$data['dump'] = true;
	$page['text'] = wrap_template('mysqldump', $data);
	return $page;
}

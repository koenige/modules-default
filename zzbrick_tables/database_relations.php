<?php 

/**
 * default module
 * Database table to set relations for upholding referential integrity
 * DB-Tabelle zur Eingabe von Beziehungen fuer die referentielle Integritaet
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2005-2010, 2014, 2018-2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


if (!wrap_access('default_relations')) wrap_quit(403);

$zz['title'] = 'Database Table Relations';
$zz['table'] = $zz_conf['relations_table'];

$zz['fields'][1]['title'] = 'ID';
$zz['fields'][1]['field_name'] = 'rel_id';
$zz['fields'][1]['type'] = 'id';

$zz['fields'][2]['title'] = 'Database of Master Table';
$zz['fields'][2]['field_name'] = 'master_db';
$zz['fields'][2]['type'] = 'select';
$zz['fields'][2]['sql'] = 'SHOW DATABASES';
$zz['fields'][2]['hide_in_list'] = true;
$zz['fields'][2]['default'] = $zz_conf['db_name'];

$zz['fields'][3]['title'] = 'Name of Master Table';
$zz['fields'][3]['title_tab'] = 'Master Table';
$zz['fields'][3]['field_name'] = 'master_table';
$zz['fields'][3]['type'] = 'text';	
$zz['fields'][3]['list_append_next'] = true;
$zz['fields'][3]['list_suffix'] = ' . ';
$zz['fields'][3]['class'] = 'block480a';
$zz['fields'][3]['sql'] = 'SHOW TABLES FROM '.$zz_conf['db_name'];
$zz['fields'][3]['dependencies'] = [4];

$zz['fields'][4]['title'] = 'Primary Key of Master Table';
$zz['fields'][4]['title_tab'] = 'Primary Key';
$zz['fields'][4]['field_name'] = 'master_field';
$zz['fields'][4]['type'] = 'text';
$zz['fields'][4]['separator'] = true;
$zz['fields'][4]['sql_dependency'][3] = 'SHOW COLUMNS FROM %s WHERE `Key` = "PRI";';

$zz['fields'][5]['title'] = 'Database of Detail Table';
$zz['fields'][5]['field_name'] = 'detail_db';
$zz['fields'][5]['type'] = 'select';
$zz['fields'][5]['sql'] = 'SHOW DATABASES';
$zz['fields'][5]['hide_in_list'] = true;
$zz['fields'][5]['default'] = $zz_conf['db_name'];

$zz['fields'][6]['title'] = 'Name of Detail Table';
$zz['fields'][6]['title_tab'] = 'Detail Table';
$zz['fields'][6]['field_name'] = 'detail_table';
$zz['fields'][6]['type'] = 'text';	
$zz['fields'][6]['list_append_next'] = true;
$zz['fields'][6]['list_suffix'] = ' . ';
$zz['fields'][6]['class'] = 'block480a';
$zz['fields'][6]['sql'] = 'SHOW TABLES FROM '.$zz_conf['db_name'];
$zz['fields'][6]['dependencies'] = [8];

$zz['fields'][8]['title'] = 'Primary Key of Detail Table';
$zz['fields'][8]['title_tab'] = 'Detail Primary Key';
$zz['fields'][8]['field_name'] = 'detail_id_field';	
$zz['fields'][8]['type'] = 'text';
$zz['fields'][8]['sql_dependency'][6] = 'SHOW COLUMNS FROM %s WHERE `Key` = "PRI";';

$zz['fields'][7]['title'] = 'Foreign Key of Detail Table';
$zz['fields'][7]['title_tab'] = 'Foreign Key';
$zz['fields'][7]['field_name'] = 'detail_field';
$zz['fields'][7]['type'] = 'text';
$zz['fields'][7]['class'] = 'block480';

$zz['fields'][10]['field_name'] = 'delete';
$zz['fields'][10]['type'] = 'select';
$zz['fields'][10]['enum'] = ['delete', 'no-delete', 'update'];
$zz['fields'][10]['default'] = 'no-delete';
$zz['fields'][10]['show_values_as_list'] = true;
$zz['fields'][10]['explanation'] = 'If main record will be deleted, what should happen with detail record?';

/*	
$zz['fields'][9]['title'] = 'URL of Detail Table';
$zz['fields'][9]['field_name'] = 'detail_url';		
$zz['fields'][9]['type'] = 'text';		
$zz['fields'][9]['hide_in_list'] = true;
*/

$zz['sql'] = 'SELECT * FROM '.$zz_conf['relations_table'];
$zz['sqlorder'] = ' ORDER BY detail_db, detail_table, detail_field';

$zz_conf['max_select'] = 200;
$zz_conf['limit'] = 20;

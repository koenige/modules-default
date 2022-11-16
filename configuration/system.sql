/**
 * default module
 * SQL queries for core, page, auth and database IDs
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020-2022 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


-- core_pages --
SELECT webpages.* FROM /*_PREFIX_*/webpages webpages
WHERE webpages.identifier IN (%s);

-- core_pages__fields --
/* _latin1'/%s' */

-- core_redirects --
SELECT * FROM /*_PREFIX_*/redirects
WHERE old_url = _latin1'%s/' OR old_url = _latin1'%s.html' OR old_url = _latin1'%s';

-- core_redirects_* --
SELECT * FROM /*_PREFIX_*/redirects WHERE old_url = _latin1'%s*';

-- core_redirects*_ --
SELECT * FROM /*_PREFIX_*/redirects WHERE old_url = _latin1'*%s';

-- core_filetypes --
SELECT CONCAT(mime_content_type, '/', mime_subtype) FROM /*_PREFIX_*/filetypes
WHERE extension = _latin1'%s';


-- page_breadcrumbs --
SELECT page_id, title
	, CONCAT(identifier, IF(STRCMP(ending, 'none'), ending, '')) AS identifier 
	, mother_page_id
FROM /*_PREFIX_*/webpages;

-- page_menu --
SELECT page_id
	, SUBSTRING_INDEX(title, "–", 1) AS title
	, CONCAT(identifier, IF(STRCMP(ending, 'none'), ending, '')) AS url
	, mother_page_id, menu
FROM /*_PREFIX_*/webpages
WHERE NOT ISNULL(menu)
AND live = 'yes'
ORDER BY sequence;

-- page_menu_level2 --

-- page_menu_level3 --

-- page_menu_level4 --

-- page_menu_hierarchy --
SELECT mother_page_id FROM /*_PREFIX_*/webpages WHERE page_id = %d;


-- auth_logout --
UPDATE /*_PREFIX_*/logins SET logged_in = 'no' WHERE login_id = %s;

-- auth_last_click --
UPDATE /*_PREFIX_*/logins SET logged_in = 'yes', last_click = %s WHERE login_id = %s;

-- auth_login --
SELECT password, username, logins.login_id AS user_id, logins.login_id
FROM /*_PREFIX_*/logins logins
WHERE active = 'yes' AND username = _latin1'%s';

-- auth_last_masquerade --

-- auth_login_masquerade --

-- auth_login_settings --


-- ids_categories --
SELECT path, category_id FROM /*_PREFIX_*/categories ORDER BY path;

-- ids-aliases_categories --
SELECT category_id, parameters FROM /*_PREFIX_*/categories WHERE parameters LIKE '%alias=%';

-- ids_languages --
SELECT CONCAT(iso_639_1, IFNULL(CONCAT('-', variation), '')), language_id FROM /*_PREFIX_*/languages WHERE website = 'yes' ORDER BY iso_639_1;

-- ids_filetypes --
SELECT filetype, filetype_id FROM /*_PREFIX_*/filetypes;

-- ids_websites --
SELECT domain, website_id FROM /*_PREFIX_*/websites;

-- ids_countries --
SELECT country_code, country_id FROM /*_PREFIX_*/countries;

-- ids_folders --
SELECT filename, medium_id FROM /*_PREFIX_*/media WHERE filetype_id = /*_ID FILETYPES folder */;


-- zzform_filetypelist --
SELECT filetype_id, UPPER(filetype) AS filetype, filetype_description FROM /*_PREFIX_*/filetypes WHERE filetype IN ('%s');

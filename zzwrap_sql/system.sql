/**
 * Zugzwang Project
 * SQL queries for core, page, auth and database IDs
 *
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


-- core_pages --
SELECT webpages.* FROM /*_PREFIX_*/webpages webpages
WHERE webpages.identifier = _latin1'%s';

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
SELECT path, category_id FROM categories ORDER BY path;

-- ids-aliases_categories --
SELECT category_id, parameters FROM categories WHERE parameters LIKE '%alias=%';

-- ids_languages --
SELECT iso_639_1, language_id FROM languages WHERE website = 'yes' ORDER BY iso_639_1;

-- ids_filetypes --
SELECT filetype, filetype_id FROM filetypes;

-- ids_websites --
SELECT domain, website_id FROM websites;

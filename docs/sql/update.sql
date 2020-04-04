/**
 * Zugzwang Project
 * SQL updates for default module
 *
 * http://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2019-2020 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */

/* 2019-11-19-1 */	ALTER TABLE `categories` CHANGE `path` `path` varchar(100) COLLATE 'latin1_general_ci' NOT NULL AFTER `main_category_id`;
/* 2020-02-25-1 */	ALTER TABLE `webpages` ADD INDEX `mother_page_id` (`mother_page_id`);
/* 2020-02-25-2 */	ALTER TABLE `redirects` CHANGE `old_url` `old_url` varchar(127) COLLATE 'latin1_general_ci' NOT NULL AFTER `redirect_id`, CHANGE `new_url` `new_url` varchar(127) COLLATE 'latin1_general_ci' NOT NULL AFTER `old_url`;
/* 2020-02-25-3 */	ALTER TABLE `_settings` CHANGE `setting_value` `setting_value` varchar(750) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `setting_key`;
/* 2020-02-25-4 */	ALTER TABLE `_settings` DROP `login_id`;
/* 2020-02-25-5 */	ALTER TABLE `_settings` ADD UNIQUE (`setting_key`);
/* 2020-02-25-6 */	DELETE FROM _relations WHERE master_table = "logins" AND detail_table = "_settings";
/* 2020-02-25-7 */	ALTER TABLE `webpages` ADD UNIQUE `identifier` (`identifier`);

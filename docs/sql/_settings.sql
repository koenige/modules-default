
CREATE TABLE `_settings` (
  `setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login_id` int(10) unsigned DEFAULT NULL,
  `setting_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `explanation` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key_login_id` (`setting_key`,`login_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

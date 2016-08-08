
CREATE TABLE `_uris` (
  `uri_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri_scheme` varchar(15) COLLATE latin1_general_ci NOT NULL,
  `uri_host` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `uri_path` varchar(128) COLLATE latin1_general_ci NOT NULL,
  `uri_query` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `content_type` varchar(127) COLLATE latin1_general_ci NOT NULL,
  `character_encoding` varchar(31) COLLATE latin1_general_ci DEFAULT NULL,
  `content_length` mediumint(8) unsigned NOT NULL,
  `user` varchar(64) COLLATE latin1_general_ci NOT NULL DEFAULT 'none',
  `status_code` smallint(6) NOT NULL,
  `etag_md5` varchar(32) COLLATE latin1_general_ci DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `hits` int(10) unsigned NOT NULL,
  `first_access` datetime NOT NULL,
  `last_access` datetime NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uri_id`),
  UNIQUE KEY `uri_scheme_uri_host_uri_path_uri_query_user` (`uri_scheme`,`uri_host`,`uri_path`,`uri_query`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

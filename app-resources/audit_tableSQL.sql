--
-- SQL Table structure for `Auditor` table
-- File version 1.93, 2021-03-05
--

CREATE TABLE IF NOT EXISTS `auditor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `res_id` varchar(250) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ipaddr` varchar(25) DEFAULT NULL,
  `time_stmp` timestamp NULL DEFAULT NULL,
  `change_type` varchar(10) DEFAULT NULL,
  `table_name` varchar(40) DEFAULT NULL,
  `fieldName` varchar(40) DEFAULT NULL,
  `OldValue` mediumtext,
  `NewValue` mediumtext,
  PRIMARY KEY (`id`),
  KEY `res_id` (`res_id`),
  KEY `table_name` (`table_name`),
  KEY `fieldName` (`fieldName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
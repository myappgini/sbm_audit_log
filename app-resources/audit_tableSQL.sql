--
-- SQL Table structure for `Auditor` table
--

CREATE TABLE IF NOT EXISTS `auditor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `res_id` int(5) unsigned DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ipaddr` varchar(25) DEFAULT NULL,
  `time_stmp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `change_type` varchar(10) DEFAULT NULL,
  `table_name` varchar(64) DEFAULT NULL,
  `fieldName` varchar(64) DEFAULT NULL,
  `OldValue` mediumtext,
  `NewValue` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
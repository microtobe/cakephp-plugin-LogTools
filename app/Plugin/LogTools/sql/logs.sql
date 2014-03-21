CREATE TABLE `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` char(100) NOT NULL DEFAULT '',
  `params` char(250) NOT NULL DEFAULT '',
  `client_ip` char(20) DEFAULT NULL,
  `process_time` float(7,2) NOT NULL DEFAULT '0.00' COMMENT '请求时间（接口实际执行时间），单位：毫秒',
  `request_time` float(7,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '请求时间（整个过程），单位：毫秒（ms）',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `process_time` (`process_time`),
  KEY `request_time` (`request_time`),
  KEY `client_ip` (`client_ip`),
  FULLTEXT KEY `params` (`params`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


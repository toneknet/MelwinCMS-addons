CREATE TABLE {{table}} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT 0,
  `ordning` int(2) DEFAULT 99,
  `companyid` int(11) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `visible` tinyint(1) DEFAULT 1,
  `label` varchar(255) DEFAULT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `price` varchar(15) DEFAULT NULL,
  `tax` int(2) DEFAULT 0,
  `connected` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

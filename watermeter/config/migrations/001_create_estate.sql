CREATE TABLE {{table}} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `adress` varchar(250) DEFAULT NULL,
  `meter` varchar(45) DEFAULT NULL,
  `propertyname` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

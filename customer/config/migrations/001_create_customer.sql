CREATE TABLE {{table}} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namn` varchar(100) NULL,
  `adress` varchar(45) NULL,
  `postnr` varchar(5) NULL,
  `postort` varchar(45) NULL,
  `fastighet` varchar(150) NULL,
  `telefon` varchar(50) NULL,
  `kommentar` text NULL,
  `ordning` int(2) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `companyid` int(11) DEFAULT 0,
  `visible` tinyint(1) DEFAULT 0,
  `fakt_adress` varchar(45) NULL,
  `fakt_postnr` varchar(5) NULL,
  `fakt_postort` varchar(45) NULL,
  `email` varchar(250) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

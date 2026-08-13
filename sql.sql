create database telemetry;


use telemetry;


CREATE TABLE `users` (
`users_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
`users_username` varchar(30) DEFAULT NULL,
`users_password` varchar(30) DEFAULT NULL,
PRIMARY KEY (`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

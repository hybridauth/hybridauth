CREATE TABLE `authentications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'refer to users.id',
  `provider` VARCHAR(100) NOT NULL DEFAULT '',
  `provider_uid` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(200) NOT NULL DEFAULT '',
  `display_name` VARCHAR(150) NOT NULL DEFAULT '',
  `first_name` VARCHAR(100) NOT NULL DEFAULT '',
  `last_name` VARCHAR(100) NOT NULL DEFAULT '',
  `profile_url` VARCHAR(300) NOT NULL DEFAULT '',
  `website_url` VARCHAR(300) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_uid` (`provider_uid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(200) NOT NULL DEFAULT '',
  `password` VARCHAR(200) NOT NULL DEFAULT '',
  `first_name` VARCHAR(200) NOT NULL DEFAULT '',
  `last_name` VARCHAR(200) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

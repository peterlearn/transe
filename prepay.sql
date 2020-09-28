CREATE TABLE IF NOT EXISTS `prepay` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(128) NOT NULL,
  `role_id` varchar(32) NOT NULL,
  `server_id` varchar(32) NOT NULL,
  `type` varchar(64) NOT NULL,
  `type_key` varchar(64) NOT NULL,
  `money` varchar(32) not null,
  `openid` varchar(64) NOT NULL,
  `time` timestamp not null DEFAULT current_timestamp,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `server_notify_lists` (
  `server_id` varchar(32) NOT NULL,
  `notify_url` varchar(64) NOT NULL,
  `time` timestamp not null DEFAULT current_timestamp,
   PRIMARY KEY (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

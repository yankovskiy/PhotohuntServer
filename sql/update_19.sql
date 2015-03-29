ALTER TABLE `users` ADD `regid` TEXT NULL ;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `from_user_id` bigint(20) NOT NULL,
  `to_user_id` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  `message` text NOT NULL,
  `status` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `from_user_id` (`from_user_id`), 
  KEY `to_user_id` (`to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `messages` ADD FOREIGN KEY ( `from_user_id` ) REFERENCES `users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

ALTER TABLE `messages` ADD FOREIGN KEY ( `to_user_id` ) REFERENCES `users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

create or replace view view_messages
as
SELECT m.id, m.from_user_id, m.to_user_id, 
fu.display_name as `from`, fu.user_id as `from_email`,
tu.display_name as `to`, tu.user_id as `to_email`,
m.date, m.message, m.status from messages m
inner join users fu on m.from_user_id = fu.id
inner join users tu on m.to_user_id = tu.id;


UPDATE `config` set `value` =  '19' where `name` = 'version';

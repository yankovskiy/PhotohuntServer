CREATE TABLE IF NOT EXISTS `favorites_users` (
  `user_id` bigint(20) NOT NULL,
  `favorite_user_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `favorites_users` ADD INDEX ( `user_id` );
ALTER TABLE `favorites_users` ADD INDEX ( `favorite_user_id` );
ALTER TABLE `favorites_users` ADD UNIQUE (
`user_id` ,
`favorite_user_id`
);

ALTER TABLE `favorites_users` ADD FOREIGN KEY ( `user_id` ) REFERENCES `users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

ALTER TABLE `favorites_users` ADD FOREIGN KEY ( `favorite_user_id` ) REFERENCES `users` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

create or replace view view_favorites_users
as
SELECT f.user_id as uid, u.id as fid, u.display_name, u.avatar from favorites_users f
inner join users u
on f.favorite_user_id = u.id;

UPDATE `config` set `value` =  '24' where `name` = 'version';

ALTER TABLE `comments` ADD `is_read` TINYINT( 1 ) NOT NULL DEFAULT '0';

create or replace view view_comments
as
select c.id, c.user_id, c.image_id, u.display_name, c.datetime, c.comment, i.user_id as owner_id, c.is_read, u.avatar from comments c
inner join users u on c.user_id = u.id
inner join images i on c.image_id = i.id;

UPDATE `config` set `value` =  '34' where `name` = 'version';
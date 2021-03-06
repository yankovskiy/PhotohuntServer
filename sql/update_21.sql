ALTER TABLE `messages` ADD `title` VARCHAR( 80 ) NOT NULL AFTER `date` ;

create or replace view view_messages
as
SELECT m.id, m.from_user_id, m.to_user_id, 
fu.display_name as `from`, fu.user_id as `from_email`,
tu.display_name as `to`, tu.user_id as `to_email`,
tu.regid as `regid`,
m.title as `title`,
m.date, m.message, m.status from messages m
inner join users fu on m.from_user_id = fu.id
inner join users tu on m.to_user_id = tu.id;


UPDATE `config` set `value` =  '21' where `name` = 'version';

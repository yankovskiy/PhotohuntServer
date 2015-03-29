ALTER TABLE `users` ADD `client_version` INT NOT NULL DEFAULT '0';
ALTER TABLE `messages` ADD `inbox` SMALLINT( 1 ) NOT NULL DEFAULT '1',
ADD `outbox` SMALLINT( 1 ) NOT NULL DEFAULT '1';

create or replace view view_messages
as
SELECT m.id, m.from_user_id, m.to_user_id, 
fu.display_name as `from`, fu.user_id as `from_email`,
tu.display_name as `to`, tu.user_id as `to_email`,
tu.regid as `regid`,
m.title as `title`,
fu.avatar as `from_avatar`,
tu.avatar as `to_avatar`,
m.inbox, m.outbox,
m.date, m.message, m.status from messages m                                                                                                                                                                        
inner join users fu on m.from_user_id = fu.id                                                                                                                                                                      
inner join users tu on m.to_user_id = tu.id;

UPDATE `config` set `value` =  '23' where `name` = 'version';

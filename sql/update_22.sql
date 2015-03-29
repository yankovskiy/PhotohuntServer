create or replace view view_messages
as
SELECT m.id, m.from_user_id, m.to_user_id, 
fu.display_name as `from`, fu.user_id as `from_email`,
tu.display_name as `to`, tu.user_id as `to_email`,
tu.regid as `regid`,
m.title as `title`,
fu.avatar as `from_avatar`,
m.date, m.message, m.status from messages m                                                                                                                                                                        
inner join users fu on m.from_user_id = fu.id                                                                                                                                                                      
inner join users tu on m.to_user_id = tu.id;

UPDATE `config` set `value` =  '22' where `name` = 'version';

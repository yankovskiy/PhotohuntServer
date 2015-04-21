CREATE OR REPLACE VIEW `view_contests` AS select `c`.`id` AS `id`,`c`.`subject` AS `subject`,`c`.`rewards` AS `rewards`,`c`.`open_date` AS `open_date`,`c`.`close_date` AS `close_date`,
`c`.`status` AS `status`,`c`.`user_id` AS `user_id`,`c`.`works` as `works`, `u`.`display_name` AS `display_name`, `u`.`avatar` AS `avatar`, `c`.`prev_id` AS `prev_id`, `c`.`winner_id` AS `winner_id`
from (`contests` `c` join `users` `u` on((`c`.`user_id` = `u`.`id`)));

UPDATE `config` set `value` =  '29' where `name` = 'version';

ALTER TABLE `contests` ADD `open_date` DATE NOT NULL AFTER `rewards`;
update contests set open_date = date_sub(close_date, interval 3 day);
CREATE OR REPLACE VIEW `view_contests` AS select `c`.`id` AS `id`,`c`.`subject` AS `subject`,`c`.`rewards` AS `rewards`,`c`.`open_date` AS `open_date`,`c`.`close_date` AS `close_date`,`c`.`status` AS `status`,`c`.`user_id` AS `user_id`,`c`.`works` as `works`, `u`.`display_name` AS `display_name` from (`contests` `c` join `users` `u` on((`c`.`user_id` = `u`.`id`)));
UPDATE `config` set `value` =  '8' where `name` = 'version';
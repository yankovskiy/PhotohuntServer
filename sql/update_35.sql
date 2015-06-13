UPDATE `goods` SET `price_money` = '20',
`disabled` = '0', `auto_use` = '1',
`min_version` = '28' WHERE `service_name` = "extra_contest";

ALTER TABLE `contests` ADD `is_user_contest` TINYINT( 1 ) NOT NULL DEFAULT '0';

CREATE OR REPLACE VIEW `view_contests` AS select `c`.`id` AS `id`,`c`.`subject` AS `subject`,`c`.`rewards` AS `rewards`,`c`.`open_date` AS `open_date`,`c`.`close_date` AS `close_date`,
`c`.`status` AS `status`,`c`.`user_id` AS `user_id`,`c`.`works` as `works`, `u`.`display_name` AS `display_name`, `u`.`avatar` AS `avatar`, `c`.`prev_id` AS `prev_id`, `c`.`winner_id` AS `winner_id`,
`c`.`is_user_contest` AS `is_user_contest`
from (`contests` `c` join `users` `u` on((`c`.`user_id` = `u`.`id`)));


UPDATE `config` set `value` =  '35' where `name` = 'version';
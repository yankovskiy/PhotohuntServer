ALTER TABLE `contests` ADD `winner_id` BIGINT NULL ;

UPDATE contests c SET winner_id = (
SELECT user_id
FROM images i
WHERE i.contest_id = c.id and i.vote_count > 0
ORDER BY vote_count DESC
LIMIT 0 , 1
) where c.status = 0;

CREATE OR REPLACE VIEW `view_contests` AS select `c`.`id` AS `id`,`c`.`subject` AS `subject`,`c`.`rewards` AS `rewards`,`c`.`open_date` AS `open_date`,`c`.`close_date` AS `close_date`,
`c`.`status` AS `status`,`c`.`user_id` AS `user_id`,`c`.`works` as `works`, `u`.`display_name` AS `display_name`, `c`.`prev_id` AS `prev_id`, `c`.`winner_id` AS `winner_id`
from (`contests` `c` join `users` `u` on((`c`.`user_id` = `u`.`id`)));

UPDATE `config` set `value` =  '15' where `name` = 'version';

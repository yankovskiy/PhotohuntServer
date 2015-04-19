CREATE OR REPLACE VIEW `view_images` 
AS 
select `i`.`id` AS `id`,`i`.`contest_id` AS `contest_id`,`i`.`user_id` AS `user_id`,`i`.`subject` AS `subject`,
`i`.`vote_count` AS `vote_count`, `i`.`must_win` AS `must_win`, `i`.`exif` AS `exif`,
`u`.`display_name` AS `display_name`, `c`.`status` AS `contest_status`, `c`.`subject` AS `contest_subject` 
from `images` `i` 
join `users` `u` on `i`.`user_id` = `u`.`id`
join `contests` `c` on `i`.`contest_id` = `c`.`id`
;

UPDATE `config` set `value` =  '28' where `name` = 'version';

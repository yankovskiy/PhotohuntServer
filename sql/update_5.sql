CREATE OR REPLACE VIEW `view_votes` AS select `v`.`id` AS `id`,`v`.`user_id` AS `user_id`,`v`.`image_id` AS `image_id`,`v`.`from` AS `from`,`i`.`contest_id` AS `contest_id` from (`votes` `v` join `images` `i` on((`i`.`id` = `v`.`image_id`)));
ALTER TABLE `users` DROP `vote_count`;
UPDATE `config` set `value` =  '5' where `name` = 'version';

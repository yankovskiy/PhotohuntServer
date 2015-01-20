ALTER TABLE `votes` ADD UNIQUE `userimagevote` ( `user_id` , `image_id` ) ;
UPDATE `config` set `value` =  '3' where `name` = 'version';

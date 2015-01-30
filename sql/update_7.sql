ALTER TABLE `users` ADD `insta` VARCHAR( 80 ) NULL;
UPDATE `config` set `value` =  '7' where `name` = 'version';
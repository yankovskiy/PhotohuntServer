ALTER TABLE `users` ADD `avatar` VARCHAR( 80 ) NULL;
UPDATE `config` set `value` =  '14' where `name` = 'version';

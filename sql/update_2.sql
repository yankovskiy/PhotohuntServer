ALTER TABLE `users` ADD `group` VARCHAR( 20 ) NOT NULL DEFAULT 'users';
UPDATE `config` set `value` =  '2' where `name` = 'version';

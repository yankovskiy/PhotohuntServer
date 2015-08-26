ALTER TABLE `users` ADD `today` TINYINT( 1 ) NOT NULL ;
UPDATE `config` set `value` =  '40' where `name` = 'version';
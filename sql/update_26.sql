ALTER TABLE `users` ADD `no_rating` SMALLINT( 1 ) NOT NULL DEFAULT '0';
UPDATE `config` set `value` =  '26' where `name` = 'version';

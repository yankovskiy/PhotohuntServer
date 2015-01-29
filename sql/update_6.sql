ALTER TABLE `users` ADD `money` INT NOT NULL DEFAULT '0', ADD `dc` INT NOT NULL DEFAULT '0';
UPDATE `users` SET `money` = `balance` ;
UPDATE `config` set `value` =  '6' where `name` = 'version';
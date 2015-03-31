ALTER TABLE `users` ADD `balance_kw` INT NOT NULL DEFAULT '0';

UPDATE `config` set `value` =  '25' where `name` = 'version';

ALTER TABLE `users` ADD `a1` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a2` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a3` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a4` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a5` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a6` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a7` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a8` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a9` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a10` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a11` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a12` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a13` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a14` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a15` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a16` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a17` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a18` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a19` SMALLINT NOT NULL;
ALTER TABLE `users` ADD `a20` SMALLINT NOT NULL;

update `users` set `a8` = -100 where `avatar` is not null;

UPDATE `config` set `value` =  '39' where `name` = 'version';
ALTER TABLE `votes` ADD `from` VARCHAR( 15 ) NULL;
UPDATE `config` set `value` =  '4' where `name` = 'version';

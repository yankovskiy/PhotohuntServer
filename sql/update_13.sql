ALTER TABLE `goods` ADD `auto_use` SMALLINT( 1 ) NOT NULL DEFAULT 0;
UPDATE `goods` SET `auto_use` = 1 WHERE `goods`.`service_name` = 'extra_photo';
UPDATE `goods` SET `auto_use` = 1 WHERE `goods`.`service_name` = 'avatar';
ALTER TABLE `items` DROP FOREIGN KEY `items_ibfk_2` ;
ALTER TABLE `items` CHANGE `good_id` `goods_id` BIGINT( 20 ) NOT NULL;
ALTER TABLE `items` ADD FOREIGN KEY ( `goods_id` ) REFERENCES `photohunt`.`goods` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
CREATE OR REPLACE VIEW `view_items` AS select `i`.`id` AS `id`,`u`.`id` AS `user_id`,`g`.`service_name` AS `service_name`,`g`.`name` AS `name`,`g`.`description` AS `description`,`i`.`count` AS `count`,
 `g`.`auto_use` AS `auto_use`
from ((`goods` `g` join `items` `i` on((`g`.`id` = `i`.`goods_id`))) join `users` `u` on((`i`.`user_id` = `u`.`id`)));

UPDATE `config` set `value` =  '13' where `name` = 'version';

ALTER TABLE `goods` ADD `disabled` TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD `min_version` INT NOT NULL DEFAULT '0';

update goods set disabled = 1 where service_name = "extra_contest";
update goods set disabled = 1 where service_name = "premium7";
update goods set disabled = 1 where service_name = "premium30";

CREATE TABLE IF NOT EXISTS `shop_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  `from` varchar(15) NOT NULL,
  `action` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE OR REPLACE VIEW view_shop_logs AS
SELECT l.id, u.user_id, u.display_name, l.date, l.from, l.action
FROM shop_logs l
INNER JOIN users u ON u.id = l.user_id;

UPDATE `config` set `value` =  '16' where `name` = 'version';

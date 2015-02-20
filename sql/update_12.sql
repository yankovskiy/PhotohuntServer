ALTER TABLE `goods` CHANGE `price` `price_money` INT( 11 ) NOT NULL;
ALTER TABLE `goods` ADD `price_dc` INT NOT NULL DEFAULT '0' AFTER `price_money`;
ALTER TABLE `goods` CHANGE `description` `description` VARCHAR( 1024 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
INSERT INTO `goods` (`id`, `service_name`, `name`, `description`, `price_money`, `price_dc`) VALUES (NULL, 'avatar', 'Аватар', 'Дает пользователю возможность загружать свои юзерпики', '5', '0'), (NULL, 'extra_contest', 'Создать свой конкурс', 'Дает возможность создать пользователю свою тему с заданной длительностью (не более 5 дней) и оплатой за победу (не более 10 очков). При публикации работ в данном конкурсе пользователи не задают новые темы, т.к. конкурс закрывается по завершении голосования.', '30', '0');
INSERT INTO `goods` (`id`, `service_name`, `name`, `description`, `price_money`, `price_dc`) VALUES (NULL, 'premium7', 'Премиум на 7 суток', 'За каждую опубликованную работу пользователь получает 2 очка рейтинга и 2 единицы money. За победу в конкурсе количество получаемых очков (рейтинг, money) увеличено в 1.5 раза с округлением в большую сторону (т.е., например, вместо 5 очков пользователь получит 8).', '0', '1'), (NULL, 'premium30', 'Премиум на 30 суток', 'За каждую опубликованную работу пользователь получает 2 очка рейтинга и 2 единицы money. За победу в конкурсе количество получаемых очков (рейтинг, money) увеличено в 1.5 раза с округлением в большую сторону (т.е., например, вместо 5 очков пользователь получит 8).', '0', '3');
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(80) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `reward_rating` int(11) NOT NULL DEFAULT '0',
  `reward_money` int(11) NOT NULL DEFAULT '0',
  `reward_dc` int(11) NOT NULL DEFAULT '0',
  `reward_good` bigint(20) NOT NULL DEFAULT '0',
  `reward_good_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_name` (`service_name`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
UPDATE `config` set `value` =  '12' where `name` = 'version';
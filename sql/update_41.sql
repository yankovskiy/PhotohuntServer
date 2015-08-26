CREATE TABLE IF NOT EXISTS `achievements_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(5) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `service_name` (`service_name`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements_logs`
--
ALTER TABLE `achievements_logs`
  ADD CONSTRAINT `achievements_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `achievements_logs_ibfk_1` FOREIGN KEY (`service_name`) REFERENCES `achievements` (`service_name`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE OR REPLACE VIEW view_achievements_logs AS
SELECT l.service_name, u.display_name, u.avatar, l.date, l.user_id
FROM `achievements_logs` l
INNER JOIN users u ON u.id = l.user_id;

UPDATE `config` set `value` =  '41' where `name` = 'version';
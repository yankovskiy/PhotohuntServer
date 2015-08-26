UPDATE `achievements` SET `max_val` = '0' WHERE `achievements`.`service_name` = 'a9' LIMIT 1 ;

UPDATE `config` set `value` =  '42' where `name` = 'version';
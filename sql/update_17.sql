update `goods` set `price_money` = 5, `description` = "Дает пользователю возможность опубликовать дополнительную фотографию в открытый конкурс" where `service_name` = "extra_photo";
update `goods` set `price_money` = 10 where `service_name` = "avatar";

UPDATE `config` set `value` =  '17' where `name` = 'version';

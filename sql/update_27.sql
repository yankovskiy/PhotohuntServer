ALTER TABLE `images` ADD `exif` TEXT NULL ,
ADD `description` TEXT NULL ;
UPDATE `config` set `value` =  '27' where `name` = 'version';

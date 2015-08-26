update `achievements` set `description` = `name`;

update `achievements` set `name` = "headshot" where `service_name` = "a1";
update `achievements` set `name` = "doubleshot" where `service_name` = "a2";
update `achievements` set `name` = "Чемпион" where `service_name` = "a3";
update `achievements` set `name` = "Вот как я могу!" where `service_name` = "a4";
update `achievements` set `name` = "Just do it" where `service_name` = "a5";
update `achievements` set `name` = "Богатенький Буратино" where `service_name` = "a6";
update `achievements` set `name` = "Home, sweet home" where `service_name` = "a7";
update `achievements` set `name` = "Я ль на свете всех милее?.." where `service_name` = "a8";
update `achievements` set `name` = "Трудоголик" where `service_name` = "a9";
update `achievements` set `name` = "Фотолюбитель" where `service_name` = "a10";
update `achievements` set `name` = "Фотоснайпер" where `service_name` = "a11";
update `achievements` set `name` = "Папарацци" where `service_name` = "a12";
update `achievements` set `name` = "Специалист" where `service_name` = "a13";
update `achievements` set `name` = "Профессионал" where `service_name` = "a14";
update `achievements` set `name` = "Эксперт" where `service_name` = "a15";
update `achievements` set `name` = "Кумир" where `service_name` = "a16";
update `achievements` set `name` = "Мэтр" where `service_name` = "a17";
update `achievements` set `name` = "Нечего скрывать" where `service_name` = "a18";
update `achievements` set `name` = "Графоман" where `service_name` = "a19";
update `achievements` set `name` = "Лев Толстой" where `service_name` = "a20";

UPDATE `config` set `value` =  '43' where `name` = 'version';
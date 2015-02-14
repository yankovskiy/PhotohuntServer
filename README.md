# PhotohuntServer
Сервер для android-приложения ["Фотокросс"](https://play.google.com/store/apps/details?id=ru.neverdark.photohunt)

### Установка
1. Создать БД в mysql
2. Экспортировать в нее запросы из папки sql. Сначала photohunt.sql, потом поочередно update_N.sql. 
3. Содержимое папки www положить в директорию доступную апачу
4. Переименовать include/config.php.example в include/config.php и отредактировать его

### Хакинг
1. vendor - содержит сторонние библиотеки
2. cron - скрипты которые выполняются через crontab
3. admin - админка
4. include - подключаемые файлы
5. index.php - основной файл приложения

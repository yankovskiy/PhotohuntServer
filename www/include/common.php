<?php
/**
 * Copyright (C) 2014-2015  Artem Yankovskiy(artemyankovskiy@gmail.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class Common {

    /**
     * Получает версию клиента
     * @return integer версия клиента (номер сборки)
     */
    public static function getClientVersion() {
        $headers = getallheaders();
        $version = 0;
        if ($headers) {
            if (isset($headers["Content-Version"])) {
                $version = $headers["Content-Version"];
            }
        }

        return $version;
    }

    /**
     * Получает IP-адрес клиента
     * @return string IP-адрес клиента
     */
    public static function getClientIp() {
        return $_SERVER["REMOTE_ADDR"];
    }

    /**
     * Вывод на экран сообщения подготовленного к логированию
     * @param string $message основное сообщение
     * @param string $detail расширенное сообщение
     */
    public static function log($message, $detail) {
        $date = date("Y-m-d H:i:s");
        printf("%s\t%s\t%s\n", $date, $message, $detail);
    }
}
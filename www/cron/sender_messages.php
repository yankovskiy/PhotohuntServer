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

require_once '../include/messagemgmt.php';

function main() {
    $msgMgmt = new MessageMgmt();
    try{
        $msgMgmt->connectToDb();

        foreach ($msgMgmt->getUnsentMessages() as $message) {
            $regid = $message->regid;
            $error = sprintf("#%d. Сообщение не отправлено:", $message->id);
            if (isset($regid) && strlen($regid) > 8) {
                try {
                    $msgMgmt->send($message);
                    $msgMgmt->markAsSent($message);
                } catch (MessageException $e) {
                    Common::log($error, $e->getMessage());
                }
            } else {
                Common::log($error, "Пользователь не зарегистрирован в GCM");
            }
        }
    } catch (PDOException $e) {
        Common::log("Ошибка при работе с БД:", $e->getMessage());
    }
}

main();
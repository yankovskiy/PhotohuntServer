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

require_once('user.php');
require_once('item.php');
require_once('config.php');
require_once('contest.php');
require_once('image.php');

/**
 * Класс для работы с базой данных
*/
class Database {
    private $mConnection;

    /**
     * Увеличивает баланс пользователя на одно очко
     * @param int $userId id пользователя
     */
    public function incrementUserBalance($userId) {
        $query = "update users set balance = balance + 1 where id = :user_id";
        $stmt = $this->mConnection->prepare($query);
        $params = array("user_id" => $userId);
        $stmt->execute($params);
    }

    /**
     * Уменьшает баланс пользователя на одно очко
     * @param int $userId id пользователя
     */
    public function decreaseUserBalance($userId) {
        $query = "update users set balance = balance - 1 where id = :user_id";
        $stmt = $this->mConnection->prepare($query);
        $params = array("user_id" => $userId);
        $stmt->execute($params);
    }

    /**
     * Меняет статус конкурса
     * @param int $days возраст конкурса в днях
     * @param int $status новый статус
     * @param int $oldStatus старый статус
     * @return boolean true в случае успешного выполнения запроса
     */
    public function changeContestStatus($days, $status, $oldStatus) {
        $query = "update contests set status = :status where TO_DAYS( NOW( ) ) - TO_DAYS( close_date ) = :days and status = :old_status";
        $stmt = $this->mConnection->prepare($query);
        $params = array("status" => $status, "days" => $days, "old_status" => $oldStatus);

        $stmt->execute($params);

        return $stmt->rowCount() == 1;
    }

    /**
     * Удаляет конкурс и все его содержимое со всех таблиц
     * @param int $id id конкурса для удаления
     */
    public function deleteContest($id) {
        $query = "delete from contests where id = :id";
        $params = array("id" => $id);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($params);
    }

    /**
     * Сбрасывает количество голосов для всех пользователей на значение по умолчанию
     */
    public function usersResetVoteCount() {
        $query = "update users set vote_count = 3";
        $stmt = $this->mConnection->query($query);
    }

    /**
     * Создает новый конкурс
     * Создает новый конкурс с темой заданной победителем в предыдущем конкурсе
     * Начисляет очки победителю
     * @return true в случае успешно создания конкурса
     */
    public function createNewContest() {
        $result = false;
        $sql = "SELECT i.id,i.subject,i.user_id,c.rewards FROM `images` i inner join contests c on (i.contest_id = c.id ) where c.status = 0 and to_days(now()) - to_days(c.close_date) = 1 order by i.vote_count desc limit 1";
        $stmt = $this->mConnection->query($sql);
         
        if ($stmt != false) {
            if ($row = $stmt->fetch()) {
                try {
                    $this->mConnection->beginTransaction();
                    $query = "insert into contests (subject, close_date, user_id) values (:subject, :close_date, :user_id)";
                    $user_id = $row["user_id"];
                    $subject = $row["subject"];
                    $rewards = $row["rewards"];
                    $close_date = date('Y-m-d', strtotime("+3 days"));
                    $params = array("user_id" => $user_id, "subject" => $subject, "close_date" => $close_date);
                    $stmt2 = $this->mConnection->prepare($query);
                    if($stmt2->execute($params)) {
                        $query = "update users set balance = balance + :rewards where id = :user_id";
                        $params = array("user_id" => $user_id, "rewards" => $rewards);
                        $stmt = $this->mConnection->prepare($query);
                        $result = $stmt->execute($params);
                        $this->mConnection->commit();
                    }
                } catch (PDOException $e) {
                    $this->mConnection->rollBack();
                }
            }
        }
         
        return $result;
    }

    /**
     * Создает новый конкурс
     * @param Contest $contestInfo объект содержащий информацию по добавляемому конкурсу
     */
    public function addContest($contestInfo) {
        $query = "insert into contests (subject, close_date, user_id, rewards, status) values (:subject, :close_date, :user_id, :rewards, :status)";
        $params = array("subject" => $contestInfo->subject, "close_date" => $contestInfo->close_date,
                "user_id" => $contestInfo->user_id, "rewards" => $contestInfo->rewards,
                "status" => $contestInfo->status);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($params);
    }

    /**
     * Выполняет подключение к базе данных, в случае ошибки генерирует исключение PDOException
     */
    public function connect() {
        $this->mConnection = new PDO(Config::DB_DSN, Config::DB_USERNAME, Config::DB_PASSWD,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    }

    /**
     * Получает информацию о пользователе по его id
     * @param int $id id пользователя
     * @return NULL в случае если запись не найдена, либо объект типа класса
     * User содержащего информацию о пользователе
     */
    public function getUserById($id) {
        $stmt = $this->mConnection->prepare("select * from users where id = :id");

        $user = null;

        if ($stmt->execute(array('id' => $id))) {
            if ($row = $stmt->fetch()) {
                $user = new User($row);
            }
        }

        return $user;
    }

    /**
     * @param int $id id записи с изображением
     * @return Image объект содержащий информацию об изображении, либо NULL если изображение не
     * найдено
     */
    public function getImageById($id) {
        $stmt = $this->mConnection->prepare("select * from view_images where id = :id");

        $image = null;
        $params = array("id" => $id);

        if($stmt->execute($params)) {
            if($row = $stmt->fetch()) {
                $image = new Image($row);
            }
        }

        return $image;
    }

    /**
     * Получает информацию по top10 пользователям
     * @return NULL в случае пустого рейтинга, либо массив объектов класса User
     */
    public function getRating() {
        $stmt = $this->mConnection->query("select id, display_name, balance from users where balance > 0 order by balance ".
                "desc limit 10");

        $ret = array();

        foreach ($stmt as $row) {
            $user = array();
            $user["id"] = $row["id"];
            $user["display_name"] = $row["display_name"];
            $user["balance"] = $row["balance"];
            $ret[] = $user;
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Получает информацию о пользователе по user_id
     * @param string $userId
     * @return NULL в случае, если запись не найдена, либо объект типа класса User содержащего
     * информацию о пользователе
     */
    public function getUserByUserId($userId) {
        $stmt = $this->mConnection->prepare("select * from users where user_id = :user_id");

        $user = null;

        if($stmt->execute(array('user_id' => $userId))) {
            if ($row = $stmt->fetch()) {
                $user = new User($row);
            }
        }

        return $user;
    }

    /**
     * Добавляет пользователя в базу данных
     * @param User $userInfo объект содержащий информацию о пользователе
     * @return boolean true в случае успешной операции
     */
    public function addUser($userInfo) {
        $query = "insert into users (user_id, display_name, password) " .
                "values (:user_id, :display_name, :password)";
        $stmt = $this->mConnection->prepare($query);

        $stmt->bindParam(":user_id", $userInfo->user_id);
        $stmt->bindParam(":display_name", $userInfo->display_name);
        $stmt->bindParam(":password", $userInfo->password);

        return $stmt->execute();
    }

    /**
     * Добавляет изображение на конкурс
     * @param Image $image объект содержащий информацию о добавляемом изображении
     * @return id Добавленной записи или -1 в случае ошибки
     */
    public function addImageToContest($image) {
        $recordId = -1;

        try {
            $this->mConnection->beginTransaction();
            $query = "insert into images (contest_id, user_id, subject) " .
                    "values (:contest_id, :user_id, :subject)";
            $stmt = $this->mConnection->prepare($query);
            $stmt->bindParam(":contest_id", $image->contest_id);
            $stmt->bindParam(":user_id", $image->user_id);
            $stmt->bindParam(":subject", $image->subject);
            $stmt->execute();

            $recordId = $this->mConnection->lastInsertId();
            $query = "update contests set works = works + 1 where id = :id";
            $stmt = $this->mConnection->prepare($query);
            $param = array("id" => $image->contest_id);
            $stmt->execute($param);

            $this->mConnection->commit();
        } catch (PDOException $e) {
            $this->mConnection->rollBack();
        }

        return $recordId;
    }

    /**
     * Проверяет имеет ли пользователь право добавить фотографию в открытый конкурс
     * Ведет подсчет уже опубликованных снимков, если снимик опубликованы проверяет наличие платных
     * публикаций, если они есть использует их
     * @param int $contest_id id конкурса
     * @param int $user_id id пользователя
     * @return array("status"=>true, "isShop" => true) status - true, если может добавить картинку
     * isShop - true, если будет использована платная возможность
     */
    public function isUserCanAddImage($contest_id, $user_id) {
        $result = array("status" => false, "is_shop" => false);

        $query = "select count(id) as count from images where contest_id = :contest_id and user_id = :user_id";
        $stmt = $this->mConnection->prepare($query);
        $params = array("contest_id" => $contest_id, "user_id" => $user_id);
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row != false) {
            $count = $row["count"];
            /* добавленные изображения найдены, нужно проверить предметы с магазина */
            if ($count > 0) {
                $itemQuery = "select count from view_items where user_id = :user_id and service_name = :service_name";
                $itemStmt = $this->mConnection->prepare($itemQuery);
                $params = array("user_id" => $user_id, "service_name" => Item::EXTRA_PHOTO);
                $itemStmt->execute($params);
                $row = $itemStmt->fetch();
                if ($row != false) {
                    $extraPhotoCount = $row["count"];
                    // у пользователя есть платная возможность размещения фото
                    if ($extraPhotoCount > 0) {
                        $result["status"] = true;
                        $result["is_shop"] = true;
                    }
                }
            } else { // пользователь еще не добавлял изображения
                $result["status"] = true;
            }
        }

        return $result;
    }

    /**
     * Использовать предмет пользователя
     * @param int $userId id пользователя
     * @param int $itemName название предмета
     * @return boolean true в случае успешного изменения данныхs
     */
    public function useUserItems($userId, $itemName) {
        $query = "update items set count = count - 1 where user_id = :user_id and good_id = (select id from goods where service_name = :service_name)";
        $stmt = $this->mConnection->prepare($query);
        $params = array("user_id" => $userId, "service_name" => $itemName);
        return $stmt->execute($params);
    }

    /**
     * Удаляет изображение из конкурса
     * @param integer $contestId id конкурса
     * @param integer $recordId id записи для удаления
     */
    public function removeImageFromContest($contestId, $recordId) {
        try{
            $this->mConnection->beginTransaction();
            $query = "update contests set works = works - 1 where id = :id";
            $stmt = $this->mConnection->prepare($query);
            $param = array("id" => $contestId);
            $stmt->execute($param);

            $query = "delete from images where id = :id";
            $stmt = $this->mConnection->prepare($query);
            $stmt->bindParam(":id", $recordId);
            $stmt->execute();
            $this->mConnection->commit();
        }catch (PDOException $e) {
            $this->mConnection->rollBack();
        }
    }

    /**
     * Меняет инфрмацию о изображении
     * @param Image $imageInfo объект содержащий информацию о изображении
     */
    public function updateImage($imageInfo) {
        $query = "update images set subject = :subject, user_id = :user_id, vote_count = :vote_count where id = :id";
        $params = array("subject" => $imageInfo->subject, "user_id" => $imageInfo->user_id,
                "vote_count" => $imageInfo->vote_count, "id" => $imageInfo->id);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($params);
    }

    /**
     * Меняет информацию о пользователе
     * @param User $userInfo новая информация о пользователе. Изменить можно только display_name,
     *     password, balance
     * @return boolean true в случае успешного изменения информации о пользователе
     */
    public function updateUser($userInfo) {
        $success = false;

        $currentRecord = $this->getUserByUserId($userInfo->user_id);
        if (isset($currentRecord)) {
            $query = "update users set display_name = :display_name, password = :password, " .
                    "balance = :balance, hash = :hash where user_id = :user_id";
            $stmt = $this->mConnection->prepare($query);

            $stmt->bindParam(":display_name", $display_name);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":balance", $balance);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":hash", $hash);

            $user_id = $userInfo->user_id;
            $display_name = (isset($userInfo->display_name)) ? $userInfo->display_name : $currentRecord->display_name;
            $password = (isset($userInfo->password)) ? $userInfo->password : $currentRecord->password;
            $balance = (isset($userInfo->balance)) ? $userInfo->balance : $currentRecord->balance;
            $hash = (isset($userInfo->hash)) ? $userInfo->hash : $currentRecord->hash;

            $success = $stmt->execute();
        }

        return $success;
    }

    /**
     * Меняет информацию о пользователе (админка)
     * @param User $userInfo новая информация о пользователе.
     * @return boolean true в случае успешного изменения информации о пользователе
     */
    public function adminUpdateUser($userInfo) {
        $success = false;
        $currentRecord = $this->getUserById($userInfo->id);
        if (isset($currentRecord)) {
            $query = "update users set `display_name` = :display_name, `password` = :password, " .
                    "`balance` = :balance, `group` = :group, `user_id` = :user_id, " .
                    "`vote_count` = :vote_count where id = :id";
            $stmt = $this->mConnection->prepare($query);

            $password = (isset($userInfo->password)) ? $userInfo->password : $currentRecord->password;
            $stmt->bindParam(":display_name", $userInfo->display_name);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":balance", $userInfo->balance);
            $stmt->bindParam(":id", $userInfo->id);
            $stmt->bindParam(":group", $userInfo->group);
            $stmt->bindParam(":user_id", $userInfo->user_id);
            $stmt->bindParam(":vote_count", $userInfo->vote_count);

            $success = $stmt->execute();
        }
        return $success;
    }

    /**
     * Создает нового пользователя (админка)
     * @param User $userInfo новая информация о пользователе.
     * @return boolean true в случае успешного добавления пользователя
     */
    public function adminAddUser($userInfo) {
        $success = false;
        $query = "insert into `users` (`display_name`, `password`, `balance`, `group`, `user_id`, `vote_count`) ".
                "values (:display_name, :password, :balance, :group, :user_id, :vote_count)";

        $stmt = $this->mConnection->prepare($query);

        $stmt->bindParam(":display_name", $userInfo->display_name);
        $stmt->bindParam(":password", $userInfo->password);
        $stmt->bindParam(":balance", $userInfo->balance);
        $stmt->bindParam(":group", $userInfo->group);
        $stmt->bindParam(":user_id", $userInfo->user_id);
        $stmt->bindParam(":vote_count", $userInfo->vote_count);

        $success = $stmt->execute();

        return $success;
    }

    /**
     * Обновляет информацию о конкурсе
     * @param Contest $contestInfo новая информация о конкурсе
     * @return boolean true в случае успешного обновления конкурса
     */
    public function updateContest($contestInfo) {
        $success = false;

        $currentContest = $this->getContest($contestInfo->id);

        if (isset($currentContest)) {
            $query = "update contests set subject = :subject, rewards = :rewards, close_date = :close_date, " .
                    "status = :status, user_id = :user_id where id = :id";
            $param = array("subject" => $contestInfo->subject, "rewards" => $contestInfo->rewards,
                    "close_date" => $contestInfo->close_date, "status" => $contestInfo->status,
                    "user_id" => $contestInfo->user_id, "id" => $contestInfo->id);
            $stmt = $this->mConnection->prepare($query);
            $success = $stmt->execute($param);
        }

        return $success;
    }

    /**
     * Получает список пользователей
     * @return массив объектов пользователей
     */
    public function getUsers() {
        $ret = array();
        $query = "select * from users";
        $stmt = $this->mConnection->query($query);
        foreach ($stmt as $row) {
            $user = new User($row);
            $ret[] = $user;
        }

        return $ret;
    }

    /**
     * Удаляет пользователя по его имени
     * @param string $userId имя пользователя
     * @return boolean true в случае успешного удаления пользователя
     */
    public function deleteUser($userId) {
        $success = false;

        $user = $this->getUserByUserId($userId);
        if ($user != null) {
            $success = $this->deleteUserById($user->id);
        }

        return $success;
    }

    /**
     * Удаляет пользователя по id строки
     * @param int $id id строки из БД
     */
    public function deleteUserById($id) {
        $success = false;

        $this->mConnection->beginTransaction();
        try {
            $updateQuery = "update contests set user_id = 1 where user_id = :id";
            $updateStmt = $this->mConnection->prepare($updateQuery);
            $updateStmt->bindParam(":id", $id);
            $updateStmt->execute();

            $updateQuery = "update images set user_id = 1 where user_id = :id";
            $updateStmt = $this->mConnection->prepare($updateQuery);
            $updateStmt->bindParam(":id", $id);
            $updateStmt->execute();

            $query = "delete from users where id = :id";
            $stmt = $this->mConnection->prepare($query);
            $stmt->bindParam(":id", $id);
            $success = $stmt->execute();
            $this->mConnection->commit();
            $success = true;
        } catch (PDOException $e) {
            $this->mConnection->rollBack();
            $success = false;
        }

        return $success;
    }

    /**
     * Получить список всех конкурсов
     * @return array массив объектов класса Contest
     */
    public function getContests() {
        $query = "select * from view_contests order by id desc";
        $stmt = $this->mConnection->query($query);
        $ret = array();

        foreach ($stmt as $row) {
            $contest = new Contest($row);
            $ret[] = $contest;
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Получить информацию о конкурсе
     * @param int $id
     * @return Contest объект содержащий информацию о конкурсе, либо null если конкурс не найден
     */
    public function getContest($id) {
        $query = "select * from view_contests where id = :id";
        $stmt = $this->mConnection->prepare($query);
        $contest = null;

        if($stmt->execute(array('id' => $id))) {
            if ($row = $stmt->fetch()) {
                $contest = new Contest($row);
            }
        }

        return $contest;
    }

    /**
     * Получает информацию по последнему проводимому конкурсу
     * @return Contest объект содержащий информацию о конкурсе, либо null если конкурс не найден
     */
    public function getLastContest() {
        $query = "select * from view_contests order by id desc limit 1";
        $contest = null;
        $stmt = $this->mConnection->query($query);

        if ($stmt != false) {
            if ($row = $stmt->fetch()) {
                $contest = new Contest($row);
            }
        }

        return $contest;
    }

    /**
     * @param id $contestId id конкурса для которого необходимо получить массив картинок
     * @param boolean $isClosed true если конкурс закрыт, при этом картинки будут отсортированны
     * по рейтингу
     * @return array массив объектов Image, либо null в случае отсутствия
     */
    public function getImagesForContest($contestId, $isClosed) {
        $images = array();
        if ($isClosed) {
            $query = "select * from view_images where contest_id = :contest_id order by vote_count desc";
        } else {
            $query = "select * from view_images where contest_id = :contest_id order by id desc";
        }
        $stmt = $this->mConnection->prepare($query);

        if ($stmt->execute(array("contest_id" => $contestId))) {
            while($row = $stmt->fetch()) {
                $image = new Image($row);
                $images[] = $image;
            }
        }

        if(count($images) == 0) {
            $images = null;
        }

        return $images;
    }

    /**
     * Получает список проголосовавших за изображение
     * @param int $imageId
     * @return массив проголосовавних за изображение
     */
    public function getVoteListForImage($imageId) {
        $query = "select u.display_name,u.user_id from users u inner join votes v on u.id = v.user_id where v.image_id = :image_id";
        $param = array("image_id" => $imageId);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($param);
        $ret = array();
        while($row = $stmt->fetch()) {
            $val = array();
            $val["display_name"] = $row["display_name"];
            $val["user_id"] = $row["user_id"];
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * Голосование за картинку. Функция увеличивает количество проголосовавших за картинку.
     * Право голоса вычитается из количества голосов у пользователя
     * @param Image $image объект содержащий информацию о картинке за которую голосовать
     * @param User $user объект содержащий информацию о пользователе
     * @return boolean true в случае успешного голосования
     */
    public function voteForImage($image, $user) {
        $success = false;
        $voteCount = $user->vote_count;
        $isAlreadyVoted = false;
        $imageId = $image->id;
        $userId = $user->id;

        // если пользователь не истратил все голоса
        if ($voteCount > 0) {
            $query = "select id from votes where user_id = :user_id and image_id = :image_id";
            $stmt = $this->mConnection->prepare($query);
            $params = array("user_id" => $userId, "image_id" => $imageId);

            if ($stmt->execute($params)) {
                if ($row = $stmt->fetch()) {
                    $isAlreadyVoted = true;
                }
            }

            // пользователь еще не голосовал
            if ($isAlreadyVoted == false) {
                $this->mConnection->beginTransaction();
                try {
                    $updateUserQuery = "update users set vote_count = :vote_count where id = :id";
                    $voteCount--;
                    $updateUserParams = array("vote_count" => $voteCount, "id" => $userId);
                    $updateUserStmt = $this->mConnection->prepare($updateUserQuery);
                    $updateUserStmt->execute($updateUserParams);

                    $updateImageQuery = "update images set vote_count = vote_count + 1 where id = :id";
                    $updateImageParams = array("id" => $imageId);
                    $updateImageStmt = $this->mConnection->prepare($updateImageQuery);
                    $updateImageStmt->execute($updateImageParams);

                    $addVoteQuery = "insert into votes (user_id, image_id) values (:user_id, :image_id)";
                    $addVoteParams = array("user_id" => $userId, "image_id"=>$imageId);
                    $addVoteStmt = $this->mConnection->prepare($addVoteQuery);
                    $addVoteStmt->execute($addVoteParams);

                    $this->mConnection->commit();
                    $success = true;
                } catch(PDOException $e) {
                    $this->mConnection->rollBack();
                }
            }
        }
        return $success;
    }

    /**
     * Сбрасывает пароль для пользователя запросившего
     * @param Strign $user user_id пользователя для сброса пароля
     * @param String $hash hash для проверки, что пользователь действительно запросил операцию
     * @param String $password новый пароль
     * @return boolean true в случае успешного сброса пароля
     */
    public function updatePassword($user, $hash, $password) {
        $query = "update users set password = :password, hash = NULL where user_id = :user_id and hash = :hash";
        $stmt = $this->mConnection->prepare($query);

        $stmt->bindParam(":user_id", $user);
        $stmt->bindParam(":hash", $hash);
        $stmt->bindParam(":password", $password);

        $stmt->execute();

        return $stmt->rowCount() == 1;
    }
}
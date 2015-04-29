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

require_once 'goods.php';
require_once 'user.php';
require_once 'item.php';
require_once 'config.php';
require_once 'contest.php';
require_once 'image.php';
require_once 'common.php';
require_once 'message.php';

/**
 * Класс для работы с базой данных
 */
class Database {
    private $mConnection;

    /**
     * Получения списка избранных пользователей
     * @param int $userId id текущего пользователя
     * @return array список избранных пользователей
     */
    public function getFavoritesUsers($userId) {
        $sql = "select * from view_favorites_users where uid = :uid";
        $params = array("uid" => $userId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
        $ret = array();

        while($row = $stmt->fetchObject()) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * Получения количества избранных пользователей
     * @param int $userId id текущего пользователя
     * @return количество избранных пользователей
     */
    public function getFavoritesUsersCount($userId) {
        $sql = "select count(uid) as count from view_favorites_users where uid = :uid";
        $params = array("uid" => $userId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        $count = 0;
        if($row = $stmt->fetch()) {
            $count = $row["count"];
        }

        return $count > 0;
    }

    /**
     * Проверяет есть ли пользователь уже в избранных
     * @param int $userId id пользователя для проверки
     * @param int $favoriteId id пользователя (избранного)
     */
    public function isFavoriteUserExists($userId, $favoriteId) {
        $sql = "select count(uid) as count from view_favorites_users where uid = :uid and fid = :fid";
        $params = array("uid" => $userId, "fid" => $favoriteId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        $count = 0;
        if($row = $stmt->fetch()) {
            $count = $row["count"];
        }

        return $count > 0;
    }

    /**
     * Удаление пользователя из списка избранных
     * @param int $userId id владельца списка
     * @param int $favoriteId id пользователя для удаления
     */
    public function removeFavoriteUser($userId, $favoriteId) {
        $sql = "delete from favorites_users where `user_id` = :uid and `favorite_user_id` = :fid";
        $params = array("uid" => $userId, "fid" => $favoriteId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Добавление пользователя в список избранных
     * @param int $userId id пользователя владельца списка
     * @param int $favoriteId id пользователя для добавления
     */
    public function addFavoriteUser($userId, $favoriteId) {
        $sql = "insert into favorites_users values (:uid, :fid)";
        $params = array("uid" => $userId, "fid" => $favoriteId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Сохраняет сообщение в базу
     * @param Message $message объект для сохранения в базу
     * @return Message объект содержащий все записи о сохраненном сообщении
     */
    public function saveMessage($message) {
        $sql = "insert into `messages` (`from_user_id`, `to_user_id`, `date`, `message`, `status`, `title`) " .
                "values (:from_user_id, :to_user_id, :date, :message, :status, :title)";

        $params = array(
                "from_user_id" => $message->from_user_id,
                "to_user_id" => $message->to_user_id,
                "date" => $message->date,
                "message" => $message->message,
                "status" => Message::UNSENT,
                "title" => $message->title
        );

        $this->mConnection->prepare($sql)->execute($params);
        $messageId = $this->mConnection->lastInsertId();
        return $this->getMessage($message->from_user_id, $messageId);
    }

    /**
     * Получает список сообщений
     * @param int $id (optional) если задан, то выбирается сообщение по его id
     * @return array массив объектов Message
     */
    public function adminGetMessages($id = null) {
        $sql = "select * from view_messages";
        $params = null;

        if (isset($id)) {
            $sql .= " where id = :id";
            $params = array("id" => $id);
        }

        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
        $messages = array();

        while ($row = $stmt->fetchObject("Message")) {
            $messages[] = $row;
        }

        return $messages;
    }

    /**
     * Возвращает количество непрочитанных сообщений для пользователя
     * @param int $userId id пользователя
     * @return int количество непрочитанных сообщений
     */
    public function getUnreadMessageCount($userId) {
        $sql = "select count(id) as count from messages where `to_user_id` = :user_id and `status` != :status and `inbox` = 1";
        $params = array("user_id" => $userId, "status" => Message::READ);

        $stmt = $this->mConnection->prepare($sql);

        $count = 0;

        if($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $count = $row["count"];
            }
        }

        return $count;
    }

    /**
     * Получает список неотправленных сообщений
     * @param boolean $sent (optional) true - если необходимо получить отправленные сообщения
     * @param int $userId (optional) id пользователя для которого нужно получить список сообщений
     * @param int $messageId (optional) id сообщения
     * @return array массив объектов Message
     */
    public function getUnsentMessages() {
        $sql = "select * from `view_messages` where `status` = :status";
        $params = array("status" => Message::UNSENT);

        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        $messages = array();
        while ($row = $stmt->fetchObject("Message")) {
            $messages[] = $row;
        }

        return $messages;
    }

    /**
     * Получает список входящих сообщений пользователя
     * @param int $userId id пользователя
     * @return array массив объектов Message
     */
    public function getInboxMessages($userId) {
        $sql = "select * from `view_messages` where `to_user_id` = :user_id and `inbox` = 1 order by `date` desc";
        $params = array("user_id" => $userId);

        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        $messages = array();
        while ($row = $stmt->fetchObject("Message")) {
            $messages[] = $row;
        }

        return $messages;
    }

    /**
     * Получает список исходящих сообщений пользователя
     * @param int $userId id пользователя
     * @return array массив объектов Message
     */
    public function getOutboxMessages($userId) {
        $sql = "select * from `view_messages` where `from_user_id` = :user_id and `outbox` = 1 order by `date` desc";
        $params = array("user_id" => $userId);

        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        $messages = array();
        while ($row = $stmt->fetchObject("Message")) {
            $messages[] = $row;
        }

        return $messages;
    }

    /**
     * Получает сообщение пользователя
     * @param int $userId id пользователя
     * @param int $messageId id сообщения
     * @return Message сообщение, либо false если сообщения не найдено
     */
    public function getMessage($userId, $messageId) {
        $sql = "select * from `view_messages` where `id` = :id and ((`from_user_id` = :user_id and `outbox` = 1) or (`to_user_id` = :user_id and `inbox` = 1))";
        $params = array("id" => $messageId, "user_id" => $userId);

        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchObject("Message");
    }

    /**
     * Отмечает сообщение в базе
     * @param int $id id сообщения для отметки
     * @param int $mark (Message::SENT, Message:UNSENT, Message::READ)
     */
    public function markMessage($id, $mark) {
        $sql = "update `messages` set `status` = :status where `id` = :id";
        $params = array("status" => $mark, "id" => $id);
        $this->mConnection->prepare($sql)->execute($params);
    }

    /**
     * Создает сообщение для отправки его пользователю
     * @param Message $message
     */
    public function adminAddMessage($message) {
        $sql = "insert into `messages` (`from_user_id`, `to_user_id`, `date`, `message`, `status`, `title`) " .
                "values (:from_user_id, :to_user_id, :date, :message, :status, :title)";

        $params = array(
                "from_user_id" => $message->from_user_id,
                "to_user_id" => $message->to_user_id,
                "date" => $message->date,
                "message" => $message->message,
                "status" => $message->status,
                "title" => $message->title
        );

        $this->mConnection->prepare($sql)->execute($params);
    }

    /**
     * Редактирует сообщение для отправки его пользователю
     * @param Message $message
     */
    public function adminUpdateMessage($message) {
        $sql = "update `messages` set `from_user_id` = :from_user_id, `to_user_id` = :to_user_id, ".
                "`date` = :date, `message` = :message, `status` = :status, `title` = :title where `id` = :id";

        $params = array(
                "id" => $message->id,
                "from_user_id" => $message->from_user_id,
                "to_user_id" => $message->to_user_id,
                "date" => $message->date,
                "message" => $message->message,
                "status" => $message->status,
                "title" => $message->title
        );

        $this->mConnection->prepare($sql)->execute($params);
    }

    /**
     * Отмечает сообщения как удаленные из папки
     * Из входящих "удаляются" только прочитанные
     * @param int $userId id пользователя для удаления сообщений
     * @param boolean $isInbox true, если сообщения "удаляются" из входящих
     */
    public function markMessagesAsRemoved($userId, $isInbox) {
        $sql = "update `messages` set ";
        $params = array("user_id" => $userId);
        if ($isInbox) {
            $sql .= "`inbox` = 0 where `to_user_id` = :user_id and `status` = :status";
            $params["status"] = Message::READ;
        } else {
            $sql .= "`outbox` = 0 where `from_user_id` = :user_id";
        }

        $this->mConnection->prepare($sql)->execute($params);
    }

    /**
     * Отмечает сообщение как удаленное из папки
     * @param int $id id сообщения для удаления
     * @param boolean $isInbox true если удалить из входящих
     */
    public function markMessageAsRemoved($id, $isInbox) {
        $sql = "update `messages` set ";

        if ($isInbox) {
            $sql .= "`inbox` = 0 ";
        } else {
            $sql .= "`outbox` = 0 ";
        }

        $sql .= "where `id` = :id";
        $params = array("id" => $id);

        $this->mConnection->prepare($sql)->execute($params);
    }

    /**
     * Удаляет сообщение по его id
     * @param int $id id сообщения для удаления
     */
    public function adminRemoveMessage($id) {
        $sql = "delete from `messages` where `id` = :id";
        $params = array("id" => $id);

        $this->mConnection->prepare($sql)->execute($params);
    }


    /**
     * Возращает количество очков рейтинга полученные за победы в конкурсах
     * @param int $id id пользователя
     * @return int количество очков рейтинга полученные за победы в конкурсах
     */
    public function getUserWinsRewards($id) {
        $reward = 0;

        $sql = "select sum(rewards) as reward from contests where winner_id = :user_id";
        $params = array("user_id" => $id);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
        if ($row = $stmt->fetch()) {
            if(isset($row["reward"])){
                $reward = $row["reward"] ;
            } else {
                $reward = 0;
            }
        }

        return $reward;
    }


    /**
     * Обновляет запись об аватаре пользователя
     * @param int $userId id пользователя
     * @param string $avatar имя файла аватара (без разрешения), либо null если аватар нужно удалить
     */
    public function updateUserAvatar($userId, $avatar) {
        $sql = "update users set avatar = :avatar where id = :id";
        $params = array("avatar" => $avatar, "id" => $userId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Проверяет наличие купленного товара у пользователя
     * @param int $userId id пользователя у которого осуществить проверку
     * @param string $serviceName service_name товара
     * @return boolean true если товар есть у пользователя
     */
    public function isGoodsExists($userId, $serviceName) {
        $sql = "select count(id) as records from `view_items` where user_id = :user_id and service_name = :service_name and count > 0";
        $params = array("user_id" => $userId, "service_name" => $serviceName);

        $stmt = $this->mConnection->prepare($sql);

        $count = 0;
        if ($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $count = $row["records"];
            }
        }

        return $count > 0;
    }

    /**
     * Получает количество побед пользователя (количество созданных их тем)
     * @param int $id id пользователя
     * @return int количество побед у пользователя
     */
    public function getUserWins($id) {
        $sql = "SELECT count(id) as count FROM `contests` WHERE winner_id = :id";
        $count = 0;

        $stmt = $this->mConnection->prepare($sql);
        $params = array("id" => $id);

        if ($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $count = $row["count"];
            }
        }

        return $count;
    }

    /**
     * Получает позицию пользователя в общем рейтинге
     * @param int $id id пользователя
     * @return int позиция в общем рейтинге
     */
    public function getUserRank($id) {
        $sql = "SELECT z.rank FROM (\n"
                . " SELECT t.id, @rownum := @rownum + 1 AS rank\n"
                        . " FROM users t, (SELECT @rownum := 0) r \n"
                                . " where no_rating = 0"
                                        . " ORDER BY balance desc, id asc\n"
                                                . ") as z WHERE id=:id";

        $rank = 0;

        $stmt = $this->mConnection->prepare($sql);
        $params = array("id" => $id);

        if ($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $rank = $row["rank"];
            }
        }

        return $rank;
    }

    /**
     * Получает список картинок пользователя отсортированных по дате публикации
     * @param int $id id пользователя
     * @return array массив объектов Image
     */
    public function getUserImages($id) {
        $data = array();

        $sql = "select id, contest_id, subject, vote_count, contest_status, contest_subject from view_images where user_id = :id order by id desc";
        $stmt = $this->mConnection->prepare($sql);
        if ($stmt->execute(array("id" => $id))) {
            while($row = $stmt->fetch()) {
                $image = new Image();
                $image->id = $row["id"];
                $image->contest_id = $row["contest_id"];
                $image->subject = $row["subject"];
                $image->vote_count = $row["vote_count"];
                $image->contest_status = $row["contest_status"];
                $image->contest_subject = $row["contest_subject"];
                $data[] = $image;
            }
        }

        return $data;
    }

    /**
     * Увеличивает баланс пользователя на одно очко
     * @param int $userId id пользователя
     */
    public function incrementUserBalance($userId) {
        $query = "update users set balance = balance + 1, `money` = `money` + 1 where id = :user_id";
        $stmt = $this->mConnection->prepare($query);
        $params = array("user_id" => $userId);
        $stmt->execute($params);
    }

    /**
     * Уменьшает баланс пользователя на одно очко
     * @param int $userId id пользователя
     */
    public function decreaseUserBalance($userId) {
        $query = "update users set balance = balance - 1, `money` = `money` - 1 where id = :user_id";
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
     * Получает список картинок за которые проголосовал пользователь в рамках конкурса
     * @param int $contestId id конкурса
     * @param int $userId id пользователя
     * @return NULL|multitype:mixed NULL - если пользователь не голосовал, array содержащий список id картинок
     */
    public function getContestVotesByUser($contestId, $userId) {
        $sql = "SELECT image_id FROM `view_votes` where `user_id` = :user_id and `contest_id` = :contest_id";
        $params = array("user_id" => $userId, "contest_id" => $contestId);
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
        $ids = array();

        while($row = $stmt->fetch()) {
            $ids[] = $row["image_id"];
        }

        if (count($ids) == 0) {
            return NULL;
        } else {
            return $ids;
        }
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
     * Создает новый конкурс
     * Создает новый конкурс с темой заданной победителем в предыдущем конкурсе
     * Начисляет очки победителю
     * @return true в случае успешно создания конкурса
     */
    public function createNewContest() {
        $result = false;
        $sql = "SELECT i.id,i.subject, i.contest_id, i.user_id,c.rewards, c.prev_id,i.vote_count FROM `images` i inner join contests c on (i.contest_id = c.id ) where c.status = 0 and to_days(now()) - to_days(c.close_date) = 1 order by i.vote_count desc, i.id asc limit 1";
        $stmt = $this->mConnection->query($sql);
         
        if ($stmt != false) {
            if ($row = $stmt->fetch()) {
                try {
                    $sql = "SELECT i.id,i.subject, i.contest_id, i.user_id,c.rewards, c.prev_id FROM `images` i inner join contests c on (i.contest_id = c.id ) where c.status = 0 and to_days(now()) - to_days(c.close_date) = 1 and i.must_win = 1";
                    $mustWin = false;

                    if ($row1 = $this->mConnection->query($sql)->fetch()) {
                        $mustWin = ($row["id"] != $row1["id"]);
                    }

                    if ($mustWin) {
                        $winner_id = $row1["user_id"];
                        $user_id = $row1["user_id"];
                        $subject = $row1["subject"];
                        $image_id = $row1["id"];
                        $vote_count = $row["vote_count"]; // количество голосов у работы-победителя
                    } else {
                        $winner_id = $row["user_id"];
                        $user_id = $row["user_id"];
                        $subject = $row["subject"];
                    }

                    $id = $row["contest_id"];
                    $prev_id = $row["contest_id"];
                    $rewards = $row["rewards"];

                    $this->mConnection->beginTransaction();

                    if ($mustWin) {
                        $sql = "update images set vote_count = :vote_count + 1 where id = :id";
                        $params = array("vote_count" => $vote_count, "id" => $image_id);
                        $this->mConnection->prepare($sql)->execute($params);
                    }

                    $sql = "update contests set winner_id = :winner_id where id = :id";
                    $params = array("winner_id" => $winner_id, "id" => $id);
                    $stmt1 = $this->mConnection->prepare($sql);
                    $stmt1->execute($params);

                    $query = "insert into contests (subject, open_date, close_date, user_id, prev_id) values (:subject, :open_date, :close_date, :user_id, :prev_id)";
                    $open_date = date('Y-m-d');
                    $close_date = date('Y-m-d', strtotime("+3 days"));
                    $params = array("user_id" => $user_id, "subject" => $subject,
                            "open_date" => $open_date, "close_date" => $close_date,
                            "prev_id" => $prev_id
                    );
                    $stmt2 = $this->mConnection->prepare($query);
                    if($stmt2->execute($params)) {
                        $query = "update users set balance = balance + :rewards, `money` = `money` + :rewards where id = :user_id";
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
     * Копирование рейтинга в квартальный рейтинг
     */
    public function copyRatingToQuarter() {
        $sql = "update `users` set `balance_kw` = `balance`";
        $this->mConnection->exec($sql);
    }

    /**
     * Создает новый конкурс
     * @param Contest $contestInfo объект содержащий информацию по добавляемому конкурсу
     */
    public function adminAddContest($contestInfo) {
        $query = "insert into contests (subject, open_date, close_date, user_id, rewards, status, prev_id) values (:subject, :open_date, :close_date, :user_id, :rewards, :status, :prev_id)";
        $params = array("subject" => $contestInfo->subject, "open_date" => $contestInfo->open_date, "close_date" => $contestInfo->close_date,
                "user_id" => $contestInfo->user_id, "rewards" => $contestInfo->rewards,
                "status" => $contestInfo->status, "prev_id" => $contestInfo->prev_id);
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
     * @param boolean $isAdmin true если доступ к функции осуществляется из админки
     * @return Image объект содержащий информацию об изображении, либо NULL если изображение не
     * найдено
     */
    public function getImageById($id, $isAdmin = true) {
        $stmt = $this->mConnection->prepare("select * from view_images where id = :id");

        $image = null;
        $params = array("id" => $id);

        if($stmt->execute($params)) {
            if($row = $stmt->fetch()) {
                $image = new Image($row, $isAdmin);
            }
        }

        return $image;
    }

    /**
     * Получает информацию по top10 пользователям
     * @return NULL в случае пустого рейтинга, либо массив объектов класса User
     */
    public function getTop10Rating() {
        $stmt = $this->mConnection->query("select id, display_name, avatar, balance from users where balance > 0 and no_rating = 0 order by balance ".
                "desc, id asc limit 10");

        $ret = array();

        while($user = $stmt->fetchObject()) {
            $ret[] = $user;
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Получает информацию по top10 из квартального рейтинга
     * @return NULL в случае пустого рейтинга, либо массив пользователей из рейтинга
     */
    public function getQuarRating() {
        $sql = "select id, display_name, avatar, (balance - balance_kw) as balance from users where no_rating = 0 having balance > 0 order by balance desc, id asc limit 10";
        $stmt = $this->mConnection->query($sql);
        $ret = array();

        while($user = $stmt->fetchObject()) {
            $ret[] = $user;
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Логгирование действий с магазином
     * @param int $id id пользователя
     * @param datetime $date дата
     * @param string $message сообщение для логгирования
     */
    public function logShopAction($id, $date, $message) {
        $sql = "insert into shop_logs (`user_id`, `date`, `from`, `action`) values (:user_id, :date, :from, :action)";
        $params = array(
                "user_id" => $id,
                "date" => $date,
                "from" => Common::getClientIp(),
                "action" => $message
        );
        $stmt = $this->mConnection->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Получает список товаров в магазине
     * @return NULL если записей нет, либо массив объектов Goods
     */
    public function getShopItems() {
        $sql = "select * from goods where disabled != 1 and min_version <= :version";
        $stmt = $this->mConnection->prepare($sql);
        $params = array("version" => Common::getClientVersion());
        $stmt->execute($params);

        $ret = array();

        while ($row = $stmt->fetch()) {
            $ret[] = new Goods($row);
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Получает описание товара из магазина
     * @param int $itemId id товара из магазина
     * @return NULL если записей нет, объект типа Goods в противном случае
     */
    public function getShopItem($itemId) {
        $sql = "select * from goods where id = :item_id";
        $params = array("item_id" => $itemId);
        $stmt = $this->mConnection->prepare($sql);

        $good = null;
        if ($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $good = new Goods($row);
            }
        }

        return $good;
    }

    /**
     * Получает список купленных вещей пользователя
     * @param int $userId id пользователя
     * @return NULL если записей нет, либо массив объектов Item
     */
    public function getUserItems($userId) {
        $ret = array();
        $sql = "SELECT `id`,`service_name`, `name`, `description`, `count`, `auto_use` from view_items where `user_id` = :user_id";
        $stmt = $this->mConnection->prepare($sql);
        $params = array("user_id" => $userId);

        if ($stmt->execute($params)) {
            while($row = $stmt->fetch()) {
                $ret[] = new Item($row);
            }
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
     * Получает количество загруженных картинок у пользователя.
     * Для чужого пользователя учитываются только картинки в закрытых конкурсах
     * @param int $id id пользователя
     * @param boolean $isSelf true если необходимо посчитать количество своих картинок
     * @return int количество картинок у пользователя
     */
    public function getUserImagesCount($id, $isSelf) {
        $sql = "select count(id) as count from view_images where user_id = :id";
        if (!$isSelf) {
            $sql .= " and contest_status = " . Contest::STATUS_CLOSE;
        }
        $params = array("id" => $id);
        $count = 0;

        $stmt = $this->mConnection->prepare($sql);
        if($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $count = $row["count"];

            }
        }

        return $count;
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
     * Осуществляет покупку предмета в магазине
     * @param User $user - пользователь осуществляющий покупку
     * @param Goods $goods - покупаемый товар
     */
    public function userBuyItem($user, $goods) {
        try {
            $this->mConnection->beginTransaction();
            $sql = "update users set money = money - :money, dc = dc - :dc where id = :id";
            $params = array(
                    "money" => $goods->price_money,
                    "dc" => $goods->price_dc,
                    "id" => $user->id
            );
            $stmt = $this->mConnection->prepare($sql);
            $stmt->execute($params);

            $sql = "select id, count(id) as count from items where user_id = :user_id and goods_id = :goods_id";
            $params = array(
                    "user_id" => $user->id,
                    "goods_id" => $goods->id
            );
            $stmt = $this->mConnection->prepare($sql);
            $stmt->execute($params);
            $count = 0;
            $id = 0;

            if ($row = $stmt->fetch()) {
                $count = $row["count"];
                $id = $row["id"];
            }

            if ($count > 0) {
                $sql = "update items set count = count + 1 where id = :id";
                $stmt = $this->mConnection->prepare($sql);
                $params = array("id" => $id);
                $stmt->execute($params);
            } else {
                $sql = "insert into items (user_id, goods_id, count) values (:user_id, :goods_id, 1)";
                $stmt = $this->mConnection->prepare($sql);
                $params = array(
                        "user_id" => $user->id,
                        "goods_id" => $goods->id
                );
                $stmt->execute($params);
            }

            $this->mConnection->commit();
        } catch (PDOException $e) {
            $this->mConnection->rollBack();
        }
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
            $query = "insert into images (`contest_id`, `user_id`, `subject`, `exif`, `description`) " .
                    "values (:contest_id, :user_id, :subject, :exif, :description)";
            $stmt = $this->mConnection->prepare($query);
            $stmt->bindParam(":contest_id", $image->contest_id);
            $stmt->bindParam(":user_id", $image->user_id);
            $stmt->bindParam(":subject", $image->subject);
            if (isset($image->exif)) {
                $stmt->bindParam(":exif", $image->exif);
            } else {
                $stmt->bindValue(":exif", null);
            }

            if (isset($image->description)) {
                $stmt->bindParam(":description", $image->description);
            } else {
                $stmt->bindValue(":description", null);
            }

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
        $query = "update view_items set count = count - 1 where user_id = :user_id and service_name = :service_name";
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
    public function adminUpdateImage($imageInfo) {
        $query = "update images set subject = :subject, user_id = :user_id, vote_count = :vote_count, must_win = :must_win where id = :id";
        $params = array("subject" => $imageInfo->subject, "user_id" => $imageInfo->user_id,
                "vote_count" => $imageInfo->vote_count, "id" => $imageInfo->id, "must_win" => $imageInfo->must_win);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($params);
    }

    /**
     * Меняет новую тему у изображения
     * @param id $id id изображения
     * @param string $subject новая тема
     */
    public function updateImageSubject($id, $subject) {
        $query = "update images set subject = :subject where id = :id";
        $params = array("subject" => $subject, "id" => $id);
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
                    "hash = :hash, insta = :insta, `regid` = :regid, `client_version` = :client_version where user_id = :user_id";
            $stmt = $this->mConnection->prepare($query);

            $stmt->bindParam(":display_name", $display_name);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":hash", $hash);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":insta", $insta);
            $stmt->bindParam(":regid", $regid);
            $stmt->bindParam(":client_version", $client_version);

            $user_id = $userInfo->user_id;
            $display_name = (isset($userInfo->display_name)) ? $userInfo->display_name : $currentRecord->display_name;
            $password = (isset($userInfo->password)) ? $userInfo->password : $currentRecord->password;
            $hash = (isset($userInfo->hash)) ? $userInfo->hash : $currentRecord->hash;
            $insta = (isset($userInfo->insta)) ? $userInfo->insta : $currentRecord->insta;
            $regid = (isset($userInfo->regid)) ? $userInfo->regid : $currentRecord->regid;
            $client_version = (isset($userInfo->client_version)) ? $userInfo->client_version : $currentRecord->client_version;

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
                    "`money` = :money, `dc` = :dc, insta = :insta " .
                    "where id = :id";
            $stmt = $this->mConnection->prepare($query);

            $password = (isset($userInfo->password)) ? $userInfo->password : $currentRecord->password;
            $stmt->bindParam(":display_name", $userInfo->display_name);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":balance", $userInfo->balance);
            $stmt->bindParam(":id", $userInfo->id);
            $stmt->bindParam(":group", $userInfo->group);
            $stmt->bindParam(":user_id", $userInfo->user_id);
            $stmt->bindParam(":money", $userInfo->money);
            $stmt->bindParam(":dc", $userInfo->dc);
            $stmt->bindParam(":insta", $userInfo->insta);

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
        $query = "insert into `users` (`display_name`, `password`, `balance`, `group`, `user_id`, `money`, `dc`, `insta`) ".
                "values (:display_name, :password, :balance, :group, :user_id, :money, :dc, :insta)";

        $stmt = $this->mConnection->prepare($query);

        $stmt->bindParam(":display_name", $userInfo->display_name);
        $stmt->bindParam(":password", $userInfo->password);
        $stmt->bindParam(":balance", $userInfo->balance);
        $stmt->bindParam(":group", $userInfo->group);
        $stmt->bindParam(":user_id", $userInfo->user_id);
        $stmt->bindParam(":money", $userInfo->money);
        $stmt->bindParam(":dc", $userInfo->dc);
        $stmt->bindParam(":insta", $userInfo->insta);

        $success = $stmt->execute();

        return $success;
    }

    /**
     * Обновляет информацию о конкурсе
     * @param Contest $contestInfo новая информация о конкурсе
     * @return boolean true в случае успешного обновления конкурса
     */
    public function adminUpdateContest($contestInfo) {
        $success = false;

        $currentContest = $this->getContest($contestInfo->id);

        if (isset($currentContest)) {
            $query = "update contests set subject = :subject, rewards = :rewards, open_date = :open_date, close_date = :close_date, " .
                    "status = :status, user_id = :user_id, prev_id = :prev_id where id = :id";
            $param = array("subject" => $contestInfo->subject, "rewards" => $contestInfo->rewards,
                    "open_date" => $contestInfo->open_date,
                    "close_date" => $contestInfo->close_date, "status" => $contestInfo->status,
                    "user_id" => $contestInfo->user_id, "id" => $contestInfo->id,
                    "prev_id" => $contestInfo->prev_id
            );
            $stmt = $this->mConnection->prepare($query);
            $success = $stmt->execute($param);
        }

        return $success;
    }

    /**
     * Получает список пользователей
     * @return массив объектов пользователей
     */
    public function adminGetUsers() {
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
     * Получить список всех закрытых конкурсов
     * @param boolean $showAll - true для отображения всех конкурсов
     * @return array массив объектов класса Contest
     */
    public function getContests($showAll = false) {
        if ($showAll) {
            $query = "select * from view_contests order by id desc";
            $stmt = $this->mConnection->query($query);
        } else {
            $query = "select * from view_contests where status = :status order by id desc";
            $stmt = $this->mConnection->prepare($query);
            $params = array("status" => Contest::STATUS_CLOSE);
            $stmt->execute($params);
        }

        $ret = array();
        while($row = $stmt->fetch()) {
            $contest = new Contest($row);
            $ret[] = $contest;
        }

        if (count($ret) == 0) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Получает список конкурсов в которых победил пользователь
     * @param int $id id пользователя
     * @return array массив объектов класса Contest, либо null если конкурсов не найдено
     */
    public function getWinsList($id) {
        $sql = "select * from view_contests where winner_id = :id order by id desc";
        $stmt = $this->mConnection->prepare($sql);
        $params = array("id" => $id);
        $stmt->execute($params);

        $ret = array();
        while($row = $stmt->fetch()) {
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
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
     * Получает информацию о всех открытых конкурсах
     * @return array массив объектов Contest содержащий информацию о всех открытых конкурсах, либо null если конкурсов нет
     */
    public function getOpenContests() {
        $query = "select * from view_contests where status != :status order by id desc";
        $contests = null;

        $stmt = $this->mConnection->prepare($query);
        $params = array("status" => Contest::STATUS_CLOSE);
        $stmt->execute($params);

        if ($stmt != false) {
            $contests = array();
            while($row = $stmt->fetch()) {
                $contest = new Contest($row);
                $contests[] = $contest;
            }
        }

        return $contests;
    }

    /**
     * @param id $contestId id конкурса для которого необходимо получить массив картинок
     * @param boolean $isClosed true если конкурс закрыт, при этом картинки будут отсортированны
     * по рейтингу
     * @param boolean $isAdmin true если обращение происходит из админки и нужно получить дополнительные поля
     * @return array массив объектов Image, либо null в случае отсутствия
     */
    public function getImagesForContest($contestId, $isClosed, $isAdmin = false) {
        $images = array();
        if ($isClosed) {
            $query = "select * from view_images where contest_id = :contest_id order by vote_count desc, id asc";
        } else {
            $query = "select * from view_images where contest_id = :contest_id order by id desc";
        }
        $stmt = $this->mConnection->prepare($query);

        if ($stmt->execute(array("contest_id" => $contestId))) {
            while($row = $stmt->fetch()) {
                $image = new Image($row, $isAdmin);
                $images[] = $image;
            }
        }

        if(count($images) == 0) {
            $images = null;
        }

        return $images;
    }

    /**
     * Получает количество потраченных голосов пользователя в определенном конкурсе
     * @param int $userId id пользователя
     * @param int $contestId id конкурса
     * @return int количество потраченных голосов пользователя
     */
    public function getVoteCount($userId, $contestId) {
        $count = 0;

        $sql = "SELECT count(id) as count FROM `view_votes` WHERE contest_id = :contest_id and user_id = :user_id";
        $params = array("contest_id" => $contestId, "user_id" => $userId);
        $stmt = $this->mConnection->prepare($sql);
        if($stmt->execute($params)) {
            if($row = $stmt->fetch()) {
                $count = $row["count"];
            }
        }

        return $count;
    }

    /**
     * Получает список проголосовавших за изображение
     * @param int $imageId
     * @return массив проголосовавних за изображение
     */
    public function adminGetVoteListForImage($imageId) {
        $query = "select u.display_name,u.user_id,v.`from` from users u inner join votes v on u.id = v.user_id where v.image_id = :image_id";
        $param = array("image_id" => $imageId);
        $stmt = $this->mConnection->prepare($query);
        $stmt->execute($param);
        $ret = array();
        while($row = $stmt->fetch()) {
            $val = array();
            $val["display_name"] = $row["display_name"];
            $val["user_id"] = $row["user_id"];
            $val["from"] = $row["from"];
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * Голосование за картинку. Функция увеличивает количество проголосовавших за картинку.
     * Если пользователь уже голосовал за картинку, то с нее списывается его голос.
     * @param Image $image объект содержащий информацию о картинке за которую голосовать
     * @param User $user объект содержащий информацию о пользователе
     * @param String $from IP-адрес клиента
     */
    public function voteForImage($image, $user, $from = NULL) {
        $isAlreadyVoted = false;
        $imageId = $image->id;
        $userId = $user->id;
        $error = null;
        $votes = Contest::MAX_VOTES - $this->getVoteCount($userId, $image->contest_id);

        $query = "select id from votes where user_id = :user_id and image_id = :image_id";
        $stmt = $this->mConnection->prepare($query);
        $params = array("user_id" => $userId, "image_id" => $imageId);

        if ($stmt->execute($params)) {
            if ($row = $stmt->fetch()) {
                $isAlreadyVoted = true;
            }
        }

        if ($isAlreadyVoted == false) {
            if ($votes > 0) {
                $this->mConnection->beginTransaction();
                try {
                    $addVoteQuery = "insert into `votes` (`user_id`, `image_id`, `from`) values (:user_id, :image_id, :from)";
                    $addVoteParams = array("user_id" => $userId, "image_id"=>$imageId, "from" => $from);
                    $addVoteStmt = $this->mConnection->prepare($addVoteQuery);
                    $addVoteStmt->execute($addVoteParams);

                    $updateImageQuery = "update images set vote_count = vote_count + 1 where id = :id";
                    $updateImageParams = array("id" => $imageId);
                    $updateImageStmt = $this->mConnection->prepare($updateImageQuery);
                    $updateImageStmt->execute($updateImageParams);

                    $this->mConnection->commit();
                } catch(PDOException $e) {
                    $this->mConnection->rollBack();
                    throw new ContestException("Ошибка при голосовании");
                }
            } else {
                throw new ContestException("У вас нет очков голосования");
            }
        } else {
            $this->mConnection->beginTransaction();
            try {
                $deleteVoteQuery = "delete from `votes` where `user_id` = :user_id and `image_id` = :image_id";
                $deleteVoteParams = array("user_id" => $userId, "image_id"=>$imageId);
                $deleteVoteStmt = $this->mConnection->prepare($deleteVoteQuery);
                $deleteVoteStmt->execute($deleteVoteParams);

                $updateImageQuery = "update images set vote_count = vote_count - 1 where id = :id";
                $updateImageParams = array("id" => $imageId);
                $updateImageStmt = $this->mConnection->prepare($updateImageQuery);
                $updateImageStmt->execute($updateImageParams);

                $this->mConnection->commit();
            } catch(PDOException $e) {
                $this->mConnection->rollBack();
                throw new ContestException("Ошибка при отмене голоса");
            }
        }
    }

    /**
     * @deprecated
     * Голосование за картинку. Функция увеличивает количество проголосовавших за картинку.
     * @param Image $image объект содержащий информацию о картинке за которую голосовать
     * @param User $user объект содержащий информацию о пользователе
     * @param String $from IP-адрес клиента
     * @return array boolean status true в случае успешного голосования, string error текст ошибки
     */
    public function voteForImage_api13($image, $user, $from = NULL) {
        $success = false;
        $isAlreadyVoted = false;
        $imageId = $image->id;
        $userId = $user->id;
        $error = null;
        $votes = Contest::MAX_VOTES - $this->getVoteCount($userId, $image->contest_id);

        // если пользователь не истратил все голоса
        if ($votes > 0) {
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
                    $addVoteQuery = "insert into `votes` (`user_id`, `image_id`, `from`) values (:user_id, :image_id, :from)";
                    $addVoteParams = array("user_id" => $userId, "image_id"=>$imageId, "from" => $from);
                    $addVoteStmt = $this->mConnection->prepare($addVoteQuery);
                    $addVoteStmt->execute($addVoteParams);

                    $updateImageQuery = "update images set vote_count = vote_count + 1 where id = :id";
                    $updateImageParams = array("id" => $imageId);
                    $updateImageStmt = $this->mConnection->prepare($updateImageQuery);
                    $updateImageStmt->execute($updateImageParams);

                    $this->mConnection->commit();
                    $success = true;
                } catch(PDOException $e) {
                    $this->mConnection->rollBack();
                    $error = "Вы уже голосовали за эту работу";
                }
            } else {
                $error = "Вы уже голосовали за эту работу";
            }
        } else {
            $error = "У вас нет очков голосования";
        }

        return array("status" => $success, "error" => $error);
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

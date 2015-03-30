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
require_once 'auth.php';
require_once 'db.php';
require_once 'exceptions.php';
require_once 'message.php';
require_once 'GCMPushMessage.php';

class MessageMgmt {
    private $mDb;

    /**
     * Выполняет подключение к базе данных
     * Генерирует исключение PDOException в случае ошибки подключения
     */
    public function connectToDb() {
        $this->mDb = new Database();
        $this->mDb->connect();
    }

    /**
     * Получает список неотправленных сообщений
     * @return Array массив объектов Message
     */
    public function getUnsentMessages() {
        return $this->mDb->getUnsentMessages();
    }

    /**
     * Получить список сообщений пользователя
     */
    public function getMyMessages() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $auth->getAuthenticatedUser();
            $inboxMessages = $this->mDb->getInboxMessages($user->id);
            $outboxMessages = $this->mDb->getOutboxMessages($user->id);

            $inbox = $this->prepareData($inboxMessages, true);
            $outbox = $this->prepareData($outboxMessages, false);

            $data = array("inbox" => $inbox, "outbox" => $outbox);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Подготовка данных для отправки клиенту
     * @param array $messages массив Message сообщений для подготовки
     * @param boolean $isInbox true если входящие
     * @return array массив для отправки
     */
    private function prepareData($messages, $isInbox) {
        $data = array();
        foreach ($messages as $message) {
            $data[] = $this->prepareElement($message, $isInbox);
        }
        return $data;
    }
    
    /**
     * Подготовка элемента к отправке
     * @param Message $message собщение для подготовки
     * @param boolean $isInbox true для входящих
     */
    private function prepareElement($message, $isInbox) {
        $msg = array();
        $msg["id"] = $message->id;
        $msg["date"] = $message->date;
        $msg["title"] = $message->title;
        $msg["message"] = $message->message;
        
        if ($isInbox) {
            $msg["from_user_id"] = $message->from_user_id;
            $msg["from"] = $message->from;
            $msg["status"] = $message->status;
            if (isset($message->from_avatar)) {
                $msg["from_avatar"] = $message->from_avatar;
            }
        } else {
            $msg["to_user_id"] = $message->to_user_id;
            $msg["to"] = $message->to;
            if (isset($message->to_avatar)) {
                $msg["to_avatar"] = $message->to_avatar;
            }
        }
        
        return $msg;
    }

    /**
     * Отмечает сообщение как прочитанное. Пользователю возвращается детальная информация о прочитанном сообщении
     * @param int $id
     * @throws MessageException
     */
    public function readMessage($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $auth->getAuthenticatedUser();

            $message = $this->mDb->getMessage($user->id, $id);
            if (!$message) {
                throw new MessageException("Попытка прочитать несуществующее сообщение");
            }

            $isInbox = ($user->id == $message->to_user_id);
            $msg = $this->prepareElement($message, $isInbox);

            if($isInbox && $message->status != Message::READ) {
                $this->markAsRead($message);
            }

            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Удаление сообщение пользователем
     * @param int $id удаляемого сообщения
     * @throws MessageException при ошибке удаления
     */
    public function removeMessage($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $auth->getAuthenticatedUser();

            if (is_numeric($id)) {
                $message = $this->mDb->getMessage($user->id, $id);
                if (!$message) {
                    throw new MessageException("Попытка удалить несуществующее сообщение");
                }
                $isInbox = ($user->id == $message->to_user_id);
    
                $this->mDb->markMessageAsRemoved($id, $isInbox);
            } else {
                if ($id == "inbox") {
                    $this->mDb->markMessagesAsRemoved($user->id, true);
                } else if ($id == "outbox") {
                    $this->mDb->markMessagesAsRemoved($user->id, false);
                }
            }
        }
    }

    /**
     * Выполняет отправку сообщения в GCM
     * @param Message $message объект содержащий информацию для отправки
     * @throws MessageException
     */
    public function send($message) {
        $gcm = new GCMPushMessage(Config::GCM_KEY);
        $device = $message->regid;

        $gcm->setDevices($device);
        $status = $gcm->send($message->to_email, false, "message");
        if ($status["http_code"] != 200) {
            $error = sprintf("Status code: %d. Read more: https://developer.android.com/google/gcm/server-ref.html", $status["http_code"]);
            throw new MessageException($error);
        } else {
            $body = json_decode($status["http_body"]);
            if ($body->failure != 0) {
                throw new MessageException($body->results[0]->error);
            }
        }
    }
    
    /**
     * Отправка сообщения пользователю
     * Записывает сообщение в входящие и исходящие
     * Отправляет уведомление пользователю на телефон (если устройство зарегистрировано в gcm)
     * @param json $body json описание объекта Message для отправки сообщения 
     */
    public function sendMessage($body) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $auth->getAuthenticatedUser();
            $message = json_decode($body);
            if (!isset($message)) {
                throw new MessageException("Сообщение не задано");
            }
            
            if (!$this->isValidMessage($message)) {
                throw new MessageException("Некорректный формат сообщения");
            }
            
            $message->from_user_id = $user->id;
            $message->date = date("Y-m-d H:i:s");
            
            $message = $this->mDb->saveMessage($message);
            try {
                $regid = $message->regid;
                if (isset($regid) && strlen($regid) > 8) {
                    $this->send($message);
                    $this->markAsSent($message);
                } 
            } catch (MessageException $e) {
                
            }
        }
    }
    
    /**
     * Проверяет корректность отправляемого сообщения
     * @param Message $message сообщение для проверки
     * @return boolean true если сообщение корректно
     */
    private function isValidMessage($message) {
        return (isset($message->title) && 
                isset($message->to_user_id) && 
                isset($message->message));
    }

    /**
     * Отмечает сообщение в базе, как отправленное
     * @param Message $message объект содержащий информацию об отправленном сообщении
     */
    public function markAsSent($message) {
        $this->mDb->markMessage($message->id, Message::SENT);
    }

    /**
     * Отмечает сообщение в базе, как прочитанное
     * @param Message $message объект содержащий информацию о прочитанном сообщении
     */
    public function markAsRead($message) {
        $this->mDb->markMessage($message->id, Message::READ);
    }
}
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

require_once 'db.php';
require_once 'auth.php';
require_once 'exceptions.php';
require_once 'contest.php';
require_once 'simpleimage.php';


/**
 * Класс для управления пользователями
 */
class UserMgmgt {
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
     * Добавляет аватар пользователя
     */
    public function addAvatar() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            if ($this->isUserHaveAvatarPermissions($user->id)) {
                
                $fileName = Config::UPLOAD_AVATAR_PATH . $user->avatar . ".jpg";
                if(file_exists($fileName)) {
                    unlink($fileName);
                }
                
                $uniq = uniqid("ava_" . $user->id);
                $fileName = Config::UPLOAD_AVATAR_PATH . $uniq . ".jpg";
                
                if (!SimpleImage::handleUploadedFile($_FILES["image"], $fileName, 512, true)) {
                    throw new UserException("Ошибка при загрузке аватара");
                }
                
                $this->mDb->updateUserAvatar($user->id, $uniq);
            } else {
                throw new UserException("У вас нет прав на выполнение этой операции");
            }
        }
    }
    
    /**
     * Удаляет аватар пользователя
     */
    public function deleteAvatar() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            if ($this->isUserHaveAvatarPermissions($user->id)) {
                $fileName = Config::UPLOAD_AVATAR_PATH . $user->avatar . ".jpg";
                try {
                    if(file_exists($fileName)) {
                        unlink($fileName);
                    }
                    
                    $this->mDb->updateUserAvatar($user->id, null);
                } catch (ErrorException $e) {
                    throw new UserException("Ошибка при удалении аватара");
                }
            } else {
                throw new UserException("У вас нет прав на выполнение этой операции");
            }
        }
    }
    
    /**
     * Получает рейтинг (top10) пользователей
     * @return boolean true в случае успешного получения рейтинга
     */
    public function getRating() {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $ratings = $this->mDb->getRating();
            if (isset($ratings)) {
                echo json_encode($ratings, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Получает список всех картинок пользователя
     * @param int $userId id пользователя
     */
    public function getUserImages($userId) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            $images = $this->mDb->getUserImages($userId);

            $sendData = array();
            foreach ($images as $image) {
                $status = $image->contest_status;
                $data = array();
                $data["id"] = $image->id;
                $data["contest_id"] = $image->contest_id;
                $data["contest_subject"] = $image->contest_subject;

                if ($status == Contest::STATUS_CLOSE) {
                    $data["vote_count"] = $image->vote_count;
                }

                if ($userId == $user->id) {
                    $data["subject"] = $image->subject;
                }

                if ($status == Contest::STATUS_CLOSE || $userId == $user->id) {
                    $sendData[] = $data;
                }

            }

            echo json_encode($sendData, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получает информацию по пользователю по его user_id или Id
     * В случае запроса по user_id - данные можно запросить только своему пользователю
     * По id - по любому
     * @param mixed $userId user_id или id пользователя
     * @return boolean true если пользователь найден
     */
    public function getUser($userId) {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {

            $isValid = false;
            $isSelf = false;

            if (is_numeric($userId)) {
                $user = $this->mDb->getUserById($userId);
                if (isset($user)) {
                    $isValid = true;
                    $isSelf = $user->user_id == $auth->getAuthenticatedUserId();
                }
            } else {
                $user = $this->mDb->getUserByUserId($userId);
                if (isset($user)) {
                    $isValid = $userId ==  $auth->getAuthenticatedUserId();
                    $isSelf = $isValid;
                }
            }

            if ($isValid) {
                $sendData = array();
                if ($user->id != 1) {
                    $sendData["rank"] = $this->mDb->getUserRank($user->id);
                    $sendData["wins_count"] = $this->mDb->getUserWins($user->id);
                    $sendData["balance"] = $user->balance;
                } else {
                    $sendData["rank"] = 0;
                    $sendData["wins_count"] = 0;
                    $sendData["balance"] = 0;
                }
                
                $sendData["id"] = $user->id;
                $isUserHaveAvatar = $this->isUserHaveAvatar($user);
                $isUserHaveAvatarPermissions = $this->isUserHaveAvatarPermissions($user->id);
                
                if ($isSelf) {
                    $sendData["avatar_permission"] = $isUserHaveAvatarPermissions;
                    $sendData["user_id"] = $user->user_id;
                    $sendData["money"] = $user->money;
                    $sendData["dc"] = $user->dc;
                }
                
                $sendData["avatar_present"] = ($isUserHaveAvatar && $isUserHaveAvatarPermissions);
                if ($sendData["avatar_present"]) {
                    $sendData["avatar"] = $user->avatar;
                }
                
                $sendData["display_name"] = $user->display_name;
                $sendData["insta"] = $user->insta;
                $sendData["images_count"] = $this->mDb->getUserImagesCount($user->id, $isSelf);
                echo json_encode($sendData, JSON_UNESCAPED_UNICODE);
                $success = true;
            }

        }

        return $success;
    }
    
    /**
     * Проверяет наличие файла аватара у пользователя
     * @param User $user объект содержащий информацию о пользователе
     * @return boolean true если у пользователя есть загруженный аватар
     */
    private function isUserHaveAvatar($user) {
        $fileName = Config::UPLOAD_AVATAR_PATH . $user->avatar . ".jpg";
        return file_exists($fileName);
    }
    
    /**
     * Проверяет наличие прав у пользователя на управление аватарами
     * @param int $userId id пользователя
     * @return boolean true если у пользователя есть права на управление аватарами
     */
    private function isUserHaveAvatarPermissions($userId) {
        return $this->mDb->isGoodsExists($userId, Item::AVATAR);
    }

    /**
     * Добавляет нового пользователя в базу
     * @param json_data $body тело сообщения содержащие информацию по добавляемому пользователю
     * @return boolean true в случае успешного добавления пользователя
     */
    public function addUser($body) {
        $success = false;

        $body = json_decode($body);
        if(isset($body) && isset($body->user_id) && isset($body->display_name) && isset($body->password)) {
            $success = (strlen($body->user_id) > 0) && (strlen($body->display_name) > 0) && (strlen($body->password) > 0);

            if ($success == true) {
                $success = $this->isValidEmail($body->user_id);
            }

            if ($success == true) {
                $userInfo = new User();
                $userInfo->user_id = $body->user_id;
                $userInfo->display_name = $body->display_name;
                $userInfo->password = Auth::crypt($userInfo->user_id, $body->password);

                $success = $this->mDb->addUser($userInfo);
            }
        }
         
        return $success;
    }

    /**
     * Обновляет информацию о пользователе. Клиент может обновить только пароль, отображаемое имя, instagram - профиль
     * @param string $userId идентификатор пользователя для обновления информации
     * @param json_data $body тело сообщения содержащее информацию по обновляемому пользователю
     * @return boolean true в случае успешного обновления пользователя
     */
    public function updateUser($userId, $body) {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            if ($userId == $auth->getAuthenticatedUserId()) {
                $body = json_decode($body);
                $user = new User();
                $user->user_id = $userId;
                if (isset($body->display_name)) {
                    $user->display_name = $body->display_name;
                }

                if (isset($body->password)) {
                    $user->password = Auth::crypt($user->user_id, $body->password);
                }

                if (isset($body->insta)) {
                    $user->insta = $body->insta;
                }

                $success = $this->mDb->updateUser($user);
            }
        }

        return $success;
    }

    /**
     * Удаляет пользователя по его имени
     * @param string $userId имя пользователя для удаления
     * @return boolean true если пользователь успешно удален
     */
    public function deleteUser($userId) {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            if ($userId == $auth->getAuthenticatedUserId()) {
                $success = $this->mDb->deleteUser($userId);
            }
        }

        return $success;
    }

    /**
     * Проверяет корректность email
     * @param String $email email для проверки на корректность
     * @return boolean true если email корректный
     */
    private function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Генерирует хеш, отправляет ссылку пользователю для сброса пароля
     * @param json_data $body тело сообщения содержащее информацию по пользователю
     * @return boolean true в случае успешной отправки ссылки пользователю
     */
    public function generateHash($body) {
        $success = false;

        $body = json_decode($body);
        if (isset($body)  && isset($body->user_id)) {
            $user = new User();
            $user->user_id = $body->user_id;
            $user->hash = uniqid();
            $success = $this->mDb->updateUser($user);
            if ($success) {
                $success = $this->sendHash($user);
            }
        }

        return $success;
    }

    /**
     * Отправяет email пользователю со ссылкой для сброса пароля
     * @param User $user содержит email и хеш для оптравки емайл
     * @return boolean true если хеш отправлен удачно
     */
    public function sendHash($user) {
        $host = $_SERVER['HTTP_HOST'];
        $subject = "Photo Hunt Online: Сброс пароля";
        $userBase64 = base64_encode($user->user_id);
        $body = "Нам пришел запрос на сброс пароля для вашей учетной записи. \nЕсли это делали не " .
                "вы, просто проигнорируйте это письмо. \nЕсли же, вы действительно хотите получить " .
                "ваш новый пароль на почту, пройдите по ссылке " .
                "http://{$host}/reset/{$userBase64}/{$user->hash}\n\n " .
                "С уважением,\n команда разработчиков сервиса Photo Hunt Online";
        return $this->sendMail($user->user_id, $subject, $body);
    }

    /**
     * Отправляет email
     * @param String $to получатель
     * @param String $subject тема письма
     * @param String $body тело письма
     * @return boolean true в случае успешной отправки
     */
    private function sendMail($to, $subject, $body) {
        $host = "tim-sw.com";
        $from = "photohunt";
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/plain; charset=utf-8";
        $headers[] = "From: <{$from}@{$host}>";
        $headers[] = "X-Mailer: PHP/".phpversion();

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Генерирует пароль и высылает его запросившему пользователю на email
     * @param String $cryptUser зашифрованное имя пользователя
     * @param String $hash хеш подтверждающий что пользователь запросил сброс пароля
     * @return boolean true в случае успешной операции
     */
    public function sendPassword($cryptUser, $hash) {
        $success = false;

        $user = base64_decode($cryptUser);
        if (strlen($user) > 0 && strlen($hash) > 0) {
            $password = $this->generatePassword(12);
            $success = $this->mDb->updatePassword($user, $hash, Auth::crypt($user, $password));

            if ($success) {
                $this->sendMail($user, "Photo Hunt Online: ваш новый пароль", "Ваш новый пароль: {$password}\n\nС уважением,\n команда разработчиков сервиса Photo Hunt Online");
                echo "Ваш новый пароль отправлен на ваш email";
            }
        }

        return $success;
    }

    /**
     * Создает случайный пароль
     * @param int $length длина пароля
     * @return String созданный пароль
     */
    private function generatePassword($length) {
        $arr = array('a','b','c','d','e','f',
                'g','h','i','j','k','l',
                'm','n','o','p','r','s',
                't','u','v','x','y','z',
                'A','B','C','D','E','F',
                'G','H','I','J','K','L',
                'M','N','O','P','R','S',
                'T','U','V','X','Y','Z',
                '1','2','3','4','5','6',
                '7','8','9','0');
        // Генерируем пароль
        $pass = "";
        for($i = 0; $i < $length; $i++)
        {
            // Вычисляем случайный индекс массива
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }
        return $pass;
    }
}
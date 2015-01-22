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
require_once 'user.php';
require_once 'contest.php';
require_once 'image.php';
require_once 'item.php';
require_once 'config.php';
require_once 'simpleimage.php';
/**
 * Класс для управления конкурсами
 */
class ContestMgmt {
    private $mDb;

    /**
     * Выполняет подключение к базе данных
     * Генерирует исключение PDOException в случае ошибки подключения
     */
    public function conenctToDb() {
        $this->mDb = new Database();
        $this->mDb->connect();
    }

    /**
     * Получает информацию о всех проводимых конкурсах
     * @return false в случае ошибки
     */
    public function getOpenContests() {
        $success = false;
        
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contests = $this->mDb->getOpenContests();
        
            if (isset($contests)) {
                echo json_encode($contests, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }
        
        return $success;
    }
    
    /**
     * Изменяет статус трехдневного конкурса на "в голосовании"
     * Конкурс должен быть открыт
     * @return boolean true в случае успешного изменения статуса конкурса
     */
    public function changeStatusToVote() {
        return $this->mDb->changeContestStatus(0, Contest::STATUS_VOTES, Contest::STATUS_OPEN);
    }

    /**
     * Изменяет статус конкурса откртого для голосования на закрытый
     * Конкурс должен быть в статусе "в голосовании" и возраст этого конкурса 4 дня
     * @return boolean true в случае успешного изменения статуса конкурса
     */
    public function changeStatusToClose() {
        return $this->mDb->changeContestStatus(1, Contest::STATUS_CLOSE, Contest::STATUS_VOTES);
    }

    /**
     * Создает новый конкурс. Данные берутся из работы победителя
     * @return true в случае успешного создания конкурса
     */
    public function createNewContest() {
        return $this->mDb->createNewContest();
    }

    /**
     * Получить список конкурсов
     * @return boolean false в случае ошибки
     */
    public function getContests() {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contests = $this->mDb->getContests();

            if (isset($contests)) {
                echo json_encode($contests, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Получает информацию по последнему конкурсу
     * @return boolean false в случае ошибки
     */
    public function getLastContest() {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contest = $this->mDb->getLastContest();

            if (isset($contest)) {
                echo json_encode($contest, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Получить полную информацию о конкурсе (вместе с картинками)
     * @param int $id id конкурса
     * @return boolean true в случае если конкурс найден
     */
    public function getContestDetails($id) {
        $success = false;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contest = $this->mDb->getContest($id);
            $isClosed = $contest->status == Contest::STATUS_CLOSE;

            if (isset($contest)) {
                $images = $this->mDb->getImagesForContest($id, $isClosed);
                if ($images != null) {
                    foreach ($images as $image) {
                        if ($isClosed == false) {
                            unset($image->subject);
                            unset($image->vote_count);
                            unset($image->display_name);
                            unset($image->user_id);
                        }
                    }
                }
                
                $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
                $votes = $user->vote_count;
                $sendData = array("contest" => $contest, "images" => $images, "votes" => $votes);
                echo json_encode($sendData, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Добавить изображение на конкурс. Изображение может быть добавлено только в открытый конкурс
     * @param int $id id конкурса
     * @return array (status, error). Boolean status false в случае ошибки, string error содержит текст ошибки
     */
    public function addImageToContest($id) {
        $success = false;
        $error = null;
        
        $auth = new Auth();
        if ($auth->authenticate($this->mDb) && isset($_POST["subject"]) && isset($_FILES["image"])) {
            $contest = $this->mDb->getContest($id);
            // конкурс существует и он в статусе "прием работ"
            if (isset($contest) && $this->isContestOpen($contest)) {
                $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
                // пользователь существует и пользователь не создатель конкурса
                if (isset($user) && $this->isUserOwnerContest($contest, $user) == false) {
                    $isUserCanAddImage = $this->isUserCanAddImage($contest, $user);
                    if ($isUserCanAddImage["status"]){
                        $image = new Image();
                        $image->contest_id = $id;
                        $image->user_id = $user->id;
                        $image->subject = $_POST["subject"];
                        $recordId = $this->mDb->addImageToContest($image);
                        if ($recordId != -1) {
                            $file = $_FILES["image"];
                            $uploadfile = Config::UPLOAD_PATH . basename($recordId . ".jpg");
                            $success = $this->handleUploadedFile($file, $uploadfile);
                            
                            if ($success) {
                                $this->mDb->incrementUserBalance($user->id);
                                // изменить количество платных публикаций
                                if ($isUserCanAddImage["is_shop"]) {
                                    $this->mDb->useUserItems($user->id, Item::EXTRA_PHOTO);
                                }
                            } else {
                                $this->mDb->removeImageFromContest($contestId, $recordId);
                            }
                        }
                    } else {
                        $error = "Вы уже загружали работу в этот конкурс";
                    }
                } else {
                    $error = "Вы не можете добавлять работы в свой конкурс";
                }
            } else {
                $error = "Работы принимаются только в открытый конкурс";
            }
        }

        return array("status" => $success, "error" => $error);
    }

    /**
     * Обрабатывает загруженное изображение.
     * Изменяет размер загруженного изображения, меняет качество
     * @param $_FILE["image"] $file загруженное изображение
     * @param string $uploadfile путь до сохраняемого изображения
     * @return boolean true в случае успешной обработки изображения
     */
    private function handleUploadedFile($file, $uploadfile) {
        $success = false;
        $whitelist = array(".jpg",".jpeg");
        try {
            
            if ($file["size"] > 1512000 || $file["size"] < 4096) {
                throw new Exception("Некорректный размер изображения");
            }

            $i = 0;
            foreach ($whitelist as $item) {
                if(preg_match("/$item/i", $file["name"])) {
                    $i++;
                }
            }
            if($i!=1) {
                throw new Exception("Неразрешенное расширение файла");
            }
            
            if ($file["type"] != "image/jpeg") {
                throw new Exception("Неразрешенный формат файла");
            }
            
            $image = new SimpleImage();
            $image->load($file["tmp_name"]);
            $width = $image->getWidth();
            $height = $image->getHeight();
            
            if ($width > $height) {
                if ($width > 1024) {
                    $width = 1024;
                }
                
                $image->resizeToWidth($width);
            } else {
                if ($height > 1024) {
                    $height = 1024;
                }
                
                $image->resizeToHeight($height);
            }
            
            $image->save($uploadfile, IMAGETYPE_JPEG, 60);
            
            $success = true;
        } catch (Exception $e) {

        }
        return $success;
    }

    /**
     * Проверяет имеет ли пользователь право добавить фотографию в открытый конкурс
     * @param Contest $contest конкурс
     * @param User $user пользователь
     * @return boolean true если пользователь имеет право добавить изображение
     */
    private function isUserCanAddImage($contest, $user) {
        return $this->mDb->isUserCanAddImage($contest->id, $user->id);
    }

    /**
     * Голосование за изображение на конкурсе. Проголосовать можно только за изображение в конкурсе
     * со статусом "идет голосование". За свои работы голосовать нельзя.
     * @param int $id id конкурса
     * @param json_data $body тело сообщения содержащие информацию по изображению за которое голосуется
     * @return array(status, error) status boolean true в случае успешного голосования за работу, 
     * string error содержит текст ошибки
     */
    public function voteForContest($id, $body) {
        $success = false;
        $error = null;
        
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contest = $this->mDb->getContest($id);
            // конкурс существует и он в статусе "голосование"
            if (isset($contest) && $this->isContestVoteForOpen($contest)) {
                $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
                // пользователь существует
                if (isset($user)) {
                    $body = json_decode($body);
                    // существует тело сообщения (переданные данные)
                    if (isset($body) && isset($body->id)) {
                        $image = $this->mDb->getImageById($body->id);
                        // картинка существует и пользователь не ее владелец
                        if (isset($image) && $this->isUserOwnerPhoto($image, $user) == false) {
                            if ($image->contest_id == $id) { 
                                $status = $this->mDb->voteForImage($image, $user);
                                $success = $status["status"];
                                $error = $status["error"];
                            } else {
                                $error = "В выбранном конкурсе нет такой работы";
                            }
                        } else {
                            $error = "За свои работы голосовать нельзя";
                        }
                    }
                }
            } else {
                $error = "Сейчас не этап голосования за работы";
            }
        }

        return array("status" => $success, "error" => $error);
    }

    /**
     * @param Contest объект содержащий информацию о конкурсе
     * @return boolean true если конкурс открыт для колосования
     */
    private function isContestVoteForOpen($contest) {
        return $contest->status == Contest::STATUS_VOTES;
    }

    /**
     * @param Contest $contest объект содержащий информацию о конкурсе
     * @param User $user объект содержащий информацию о пользователе
     * @return boolean true если пользователь является создателем конкурса
     */
    private function isUserOwnerContest($contest, $user) {
        return $contest->user_id == $user->id;
    }

    /**
     * @param Contest $contest
     * @return boolean true если конкурс открыт для приема работ
     */
    private function isContestOpen($contest) {
        return $contest->status == Contest::STATUS_OPEN;
    }

    /**
     * @param Image $image
     * @param User $user
     * @return boolean true если пользователь владелец картинки
     */
    private function isUserOwnerPhoto($image, $user) {
        return $image->user_id == $user->id;
    }
}
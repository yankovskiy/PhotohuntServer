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
require_once 'common.php';
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
     * Указывает классу использовать созданное ранее подключение к базе данных
     * @param Database $db
     */
    public function setDbConnection($db) {
        $this->mDb = $db;
    }

    /**
     * Получаение списка открытых конкурсов и дополнительной информации о пользователе
     */
    public function getOpenContests() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contests = $this->mDb->getOpenContests();
            $user = $auth->getAuthenticatedUser();

            if (empty($contests)) {
                throw new ContestException("Конкурсов не найдено");
            }

            $data = array(
                    "is_can_create" => $this->mDb->isGoodsExists($user->id, Item::EXTRA_CONTEST),
                    "contests" => $contests
            );

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @deprecated
     * Получает информацию о всех проводимых конкурсах
     * @return false в случае ошибки
     */
    public function getOpenContests_api28() {
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
     * Создает новые конкурсы. Данные берутся из работы победителя
     */
    public function createNewContests() {
        return $this->mDb->createNewContests();
    }

    /**
     * Создание пользовательского конкурса
     * @param json_data $body данные о создаваемом конкурсе
     */
    public function createUserContest($body) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $auth->getAuthenticatedUser();

            if (empty($body)) {
                throw new ContestException("Не задана информация о новом конкурсе");
            }

            if (!$this->mDb->isGoodsExists($user->id, Item::EXTRA_CONTEST)) {
                throw new ContestException("Вы не купили возможность создавать новый конкурс");
            }

            $body = json_decode($body);
            if (empty($body->subject)) {
                throw new ContestException("Не задана тема конкурса");
            }

            if (empty($body->rewards)) {
                throw new ContestException("Не задана награда за конкурс");
            }

            if (!is_numeric($body->rewards) || $body->rewards > 10 || $body->rewards <= 0) {
                throw new ContestException("Некорректное значение награды за конкурс");
            }

            if ($this->mDb->useUserItems($user->id, ITEM::EXTRA_CONTEST)) {
                $contest = new Contest();
                $contest->user_id = $user->id;
                $contest->subject = $body->subject;
                $contest->rewards = $body->rewards;
                $this->mDb->createUserContest($contest);
                $date =  date("Y-m-d H:i:s");
                $message = sprintf("Использование покупки %s", ITEM::EXTRA_CONTEST);
                $this->mDb->logShopAction($user->id, $date, $message);
            }
        }
    }

    /**
     * Получить список закрытых конкурсов
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
     * Получает количество оставшихся голосов у пользователя для конкретного конкурса
     * @param User $user объект содержащий информацию о пользователе
     * @param Contest $contest объект содержащий информацию о конкурсе
     * @return int количество оставшихся голосов у пользователя в рамках конкурса
     */
    private function getVoteCount($user, $contest) {
        return Contest::MAX_VOTES - $this->mDb->getVoteCount($user->id, $contest->id);
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

            if (isset($contest)) {
                $isClosed = $this->isContestClose($contest);
                $user = $auth->getAuthenticatedUser();
                $votes = $this->getVoteCount($user, $contest);
                $images = $this->mDb->getImagesForContest($id, $isClosed);
                $voteList = $this->mDb->getContestVotesByUser($contest->id, $user->id);

                $data = null;

                if ($images != null) {
                    $count = 0; // если конкурс закрыт первая работа в списке победитель

                    $data = array();

                    foreach ($images as $image) {
                        $img = array();

                        $img["id"] = $image->id;
                        $img["contest_id"] = $image->contest_id;
                        $img["comments_count"] = $image->comments_count;
                        $img["contest_status"] = $image->contest_status;

                        if ($this->isContestOpenForVote($contest)) {
                            $img["is_voted"] = isset($voteList) && in_array($image->id, $voteList);
                        }

                        if ($this->isContestOpen($contest)) {
                            $img["is_editable"] = $this->isUserOwnerPhoto($image, $user);
                        }

                        if (!empty($image->exif) && strlen($image->exif) > 0) {
                            $img["exif"] = json_decode($image->exif);
                        }

                        if (isset($image->description) && strlen($image->description) > 0) {
                            $img["description"] = $image->description;
                        }

                        if ($isClosed) {
                            if (isset($image->avatar) && strlen($image->avatar) > 0) {
                                $img["avatar"] = $image->avatar;
                            }
                            $img["display_name"] = $image->display_name;
                            $img["vote_count"] = $image->vote_count;
                            $img["user_id"] = $image->user_id;

                            if ($count == 0) {
                                $img["subject"] = $image->subject;
                            }
                        }

                        if ($this->isUserOwnerPhoto($image, $user)) {
                            if (isset($image->avatar) && strlen($image->avatar) > 0) {
                                $img["avatar"] = $image->avatar;
                            }
                            $img["subject"] = $image->subject;
                            $img["user_id"] = $image->user_id;
                            $img["display_name"] = $image->display_name;
                        }

                        $data[] = $img;
                        $count++;
                    }
                }

                $contestData = array();
                $contestData["id"] = $contest->id;
                $contestData["subject"] = $contest->subject;
                $contestData["rewards"] = $contest->rewards;
                $contestData["open_date"] = $contest->open_date;
                $contestData["close_date"] = $contest->close_date;
                $contestData["status"] = $contest->status;
                $contestData["user_id"] = $contest->user_id;
                $contestData["display_name"] = $contest->display_name;
                $contestData["works"] = $contest->works;
                $contestData["prev_id"] = $contest->prev_id;
                $contestData["is_user_contest"] = $contest->is_user_contest;
                if (isset($contest->avatar) && strlen($contest->avatar) > 0) {
                    $contestData["avatar"] = $contest->avatar;
                }
                $sendData = array("contest" => $contestData, "images" => $data, "votes" => $votes);
                echo json_encode($sendData, JSON_UNESCAPED_UNICODE);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Подготовка данных об изображении для отправки
     * @param Image $image объект содержащий информацию о картинке
     * @param Contest $contest объект содержащий информацию о конкурсе
     * @return array массив содержащий информацию о картинке для отправки клиенту
     */
    private function prepareImageData($image, $contest) {

    }

    /**
     * Получение информации о картинке по ее id
     * Запросить информацию можно только по своей картинке
     * @param int $imageId id изображения для получения информации
     */
    public function getImageById($imageId) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $image = $this->mDb->getImageById($imageId);
            if (empty($image)) {
                throw new ContestException("Запрос несуществующей фотографии");
            }

            $user = $auth->getAuthenticatedUser();
            if ($user->id != $image->user_id) {
                throw new ContestException("Запрос чужой фотографии");
            }

            $img = array();

            $img["id"] = $image->id;
            $img["contest_id"] = $image->contest_id;
            $img["comments_count"] = $image->comments_count;
            $img["contest_status"] = $image->contest_status;

            if ($image->contest_status == Contest::STATUS_VOTES) {
                $img["is_voted"] = isset($voteList) && in_array($image->id, $voteList);
            }

            if ($image->contest_status == Contest::STATUS_OPEN) {
                $img["is_editable"] = $this->isUserOwnerPhoto($image, $user);
            }

            if (!empty($image->exif)) {
                $img["exif"] = json_decode($image->exif);
            }

            if (isset($image->description) && strlen($image->description) > 0) {
                $img["description"] = $image->description;
            }

            if (isset($image->avatar) && strlen($image->avatar) > 0) {
                $img["avatar"] = $image->avatar;
            }
            $img["display_name"] = $image->display_name;
            $img["user_id"] = $image->user_id;
            $img["subject"] = $image->subject;

            if ($image->contest_status == Contest::STATUS_CLOSE) {
                $img["vote_count"] = $image->vote_count;
            }

            echo json_encode($img, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Проверяет, является ли конкурс пользовательским
     * @param Contest $contest информация о конкурсе
     * @return boolean true если конкурс пользовательский
     */
    private function isUserContest($contest) {
        return $contest->is_user_contest == 1;
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
                // пользователь существует и пользователь не создатель конкурса, или это пользовательский конкурс
                if (isset($user) && ($this->isUserOwnerContest($contest, $user) == false || $this->isUserContest($contest))) {
                    $isUserCanAddImage = $this->isUserCanAddImage($contest, $user);
                    if ($isUserCanAddImage["status"]){
                        $image = new Image();
                        $image->contest_id = $id;
                        $image->user_id = $user->id;
                        $image->subject = $_POST["subject"];
                        if (isset($_POST["exif"]) && strlen($_POST["exif"]) > 0) {
                            $image->exif = $this->prepareExif($_POST["exif"]);
                        }

                        if (isset($_POST["description"]) && strlen($_POST["description"]) > 0) {
                            $image->description = $_POST["description"];
                        }

                        $recordId = $this->mDb->addImageToContest($image);
                        if ($recordId > 0) {
                            $file = $_FILES["image"];
                            $uploadfile = Config::UPLOAD_PATH . basename($recordId . ".jpg");
                            $success = $this->handleUploadedFile($file, $uploadfile);
                            if ($success) {
                                $this->mDb->incrementUserBalance($user->id);
                                // изменить количество платных публикаций
                                if ($isUserCanAddImage["is_shop"]) {
                                    $this->mDb->useUserItems($user->id, Item::EXTRA_PHOTO);

                                    $date =  date("Y-m-d H:i:s");
                                    $message = sprintf("Использование покупки %s", ITEM::EXTRA_PHOTO);

                                    $this->mDb->logShopAction($user->id, $date, $message);

                                }
                            } else {
                                $this->mDb->removeImageFromContest($contestId, $recordId);
                            }
                        } else {
                            $error = "Ошибка при загрузке изображения";
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
     * Подгатавливает exif для публикации в базу
     * @param json $data
     * @return Exif
     */
    private function prepareExif($data) {
        $empty = true;
        $data = json_decode($_POST["exif"]);
        if (!empty($data)) {
            $exif = new Exif();

            if (isset($data->aperture)) {
                $empty = false;
                $exif->aperture = $data->aperture;
            }

            if (isset($data->datetime)) {
                $empty = false;
                $exif->datetime = $data->datetime;
            }

            if (isset($data->exposure_time)) {
                $empty = false;
                $exif->exposure_time = $data->exposure_time;
            }

            if (isset($data->iso)) {
                $empty = false;
                $exif->iso = $data->iso;
            }

            if (isset($data->make)) {
                $empty = false;
                $exif->make = $data->make;
            }

            if (isset($data->model)) {
                $empty = false;
                $exif->model = $data->model;
            }

            if (isset($data->focal_length)) {
                $empty = false;
                $exif->focal_length = $data->focal_length;
            }
        }

        if (!$empty) {
            return json_encode($exif, JSON_UNESCAPED_UNICODE);
        } else {
            return null;
        }
    }

    /**
     * Загрузка изображения в конкурс через админку
     * @param Image $image картинка для загрузки
     */
    public function adminAddImage($image) {
        $recordId = $this->mDb->addImageToContest($image);
        if ($recordId != -1) {
            $file = $_FILES["image"];
            $uploadfile = Config::UPLOAD_PATH . basename($recordId . ".jpg");

            $success = $this->handleUploadedFile($file, $uploadfile);

            if ($success) {
                $this->mDb->incrementUserBalance($image->user_id);
            } else {
                $this->mDb->removeImageFromContest($image->contest_id, $recordId);
            }
        }
    }

    /**
     * Обрабатывает загруженное изображение.
     * Изменяет размер загруженного изображения, меняет качество
     * @param $_FILE["image"] $file загруженное изображение
     * @param string $uploadfile путь до сохраняемого изображения
     * @return boolean true в случае успешной обработки изображения
     */
    private function handleUploadedFile($file, $uploadfile) {
        return SimpleImage::handleUploadedFile($file, $uploadfile, 1024);
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
     * Голосование за изображение в конкурсе. Проголосовать можно только за изображение в конкурсе
     * со статусом "идет голосование". За свои работы голосовать нельзя.
     * @param int $id id конкурса
     * @param json_data $body тело сообщения содержащие информацию по изображению за которое голосуется
     */
    public function voteForContest($id, $body) {
        $success = false;
        $error = null;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contest = $this->mDb->getContest($id);
            if (!isset($contest)) {
                throw new ContestException("Выбранного конкурса не существует");
            }

            if (!$this->isContestOpenForVote($contest)) {
                throw new ContestException("Сейчас не этап голосования за работы");
            }

            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            if (!isset($user)) {
                throw new ContestException("Несуществующий пользователь");
            }

            $body = json_decode($body);
            if (!isset($body) || !isset($body->id)) {
                throw new ContestException("Не задано тело сообщения");
            }

            $image = $this->mDb->getImageById($body->id);
            if (!isset($image)) {
                throw new ContestException("Несуществующее изображение");
            }

            if ($this->isUserOwnerPhoto($image, $user)) {
                throw new ContestException("За свои работы голосовать нельзя");
            }

            if ($image->contest_id != $id) {
                throw new ContestException("В выбранном конкурсе нет такой работы");
            }

            $this->mDb->voteForImage($image, $user, Common::getClientIp());
        }

    }

    /**
     * @deprecated
     * Голосование за изображение на конкурсе. Проголосовать можно только за изображение в конкурсе
     * со статусом "идет голосование". За свои работы голосовать нельзя.
     * @param int $id id конкурса
     * @param json_data $body тело сообщения содержащие информацию по изображению за которое голосуется
     * @return array(status, error) status boolean true в случае успешного голосования за работу,
     * string error содержит текст ошибки
     */
    public function voteForContest_api13($id, $body) {
        $success = false;
        $error = null;

        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $contest = $this->mDb->getContest($id);
            // конкурс существует и он в статусе "голосование"
            if (isset($contest) && $this->isContestOpenForVote($contest)) {
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
                                $status = $this->mDb->voteForImage_api13($image, $user, Common::getClientIp());
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
     * Удаляет изображение из конкурса.
     * Удалить можно только свое изображение из конкурса в статусе "Прием работ".
     * @param int $id id изображения для удаления
     */
    public function deleteImage($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $image = $this->mDb->getImageById($id);
            if (!isset($image)) {
                throw new ContestException("Указанного изображения не существует");
            }

            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            if (!$this->isUserOwnerPhoto($image, $user)) {
                throw new ContestException("Вы не владелец этого изображения");
            }

            $contest = $this->mDb->getContest($image->contest_id);
            if (!isset($contest)) {
                throw new ContestException("Конкурса не существует");
            }

            if (!$this->isContestOpen($contest)) {
                throw new ContestException("Удаление возможно только из конкурсов на этапе приёма работ");
            }

            $fileName = Config::UPLOAD_PATH . $image->id . ".jpg";
            unlink($fileName);
            $this->mDb->removeImageFromContest($image->contest_id, $image->id);
            $this->mDb->decreaseUserBalance($image->user_id);
        }
    }

    /**
     * Меняет "новую тему" у своего изображения
     * @param id $id id изображения для смены темы
     * @param json_data $body тело содержащее новую тему
     */
    public function updateImage($id, $body) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $image = $this->mDb->getImageById($id);
            if (empty($image)) {
                throw new ContestException("Указанного изображения не существует");
            }

            $user = $auth->getAuthenticatedUser();
            if (!$this->isUserOwnerPhoto($image, $user)) {
                throw new ContestException("Вы не владелец этого изображения");
            }

            $contest = $this->mDb->getContest($image->contest_id);
            if (empty($contest)) {
                throw new ContestException("Конкурса не существует");
            }

            if (!$this->isContestOpen($contest)) {
                throw new ContestException("Изменение темы возможно только в конкурсах на этапе приёма работ");
            }

            $body = json_decode($body);
            $image = new Image();
            $image->id = $id;

            if (!isset($body) || ((!isset($body->subject) || strlen($body->subject) == 0) && $contest->is_user_contest == 0)) {
                throw new ContestException("Не задана новая тема");
            }

            if ($contest->is_user_contest == 0) {
                $image->subject = $body->subject;
            }

            if (isset($body->description) && strlen($body->description) > 0) {
                $image->description = $body->description;
            }
            $this->mDb->updateImage($image);
        }
    }

    /**
     * @param Contest $contest объект содержащий информацию о конкурсе
     * @return boolean true если конкурс закрыт
     */
    private function isContestClose($contest) {
        return $contest->status == Contest::STATUS_CLOSE;
    }

    /**
     * @param Contest $contest объект содержащий информацию о конкурсе
     * @return boolean true если конкурс открыт для колосования
     */
    private function isContestOpenForVote($contest) {
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
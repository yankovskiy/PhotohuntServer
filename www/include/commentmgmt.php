<?php
require_once 'GCMPushMessage.php';
require_once 'contest.php';
require_once 'image.php';
require_once 'db.php';
require_once 'exceptions.php';
require_once 'achievementsmgmt.php';

class CommentMgmgt {
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
     * Выполняет отправку сообщения в GCM
     * @param int $userId id пользователя для отправки сообщения
     * @throws MessageException
     */
    private function gcmSendMessage($userId) {
        $user = $this->mDb->getUserById($userId);
        $device = $user->regid;
        
        if (isset($device) && strlen($device) > 0) {
            $gcm = new GCMPushMessage(Config::GCM_KEY);
            $gcm->setDevices($device);
            $gcm->send($user->user_id, false, "comment");
        }
    }

    /**
     * Получение списка непрочитанных комментариев
     */
    public function getUnreadComments() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $comments = $this->mDb->getUnreadComments($auth->getAuthenticatedUser()->id);
            $data = array();
            if ($comments != null) {
                foreach($comments as $comment) {
                    $row = array();
                    $row["image_id"] = $comment->image_id;
                    $row["display_name"] = $comment->display_name;
                    if (isset($comment->avatar) && strlen($comment->avatar) > 0) {
                        $row["avatar"] = $comment->avatar;
                    }
                    $row["datetime"] = $comment->datetime;
                    $row["comment"] = $comment->comment;
                    $data[] = $row;
                }
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получение списка комментариев для фотографии.
     * Если конкурс не закрыт, то комментарии автора изображения анонимны (отсылка данных об авторе
     * не проивзодится).
     * @param int $id id фотографии
     */
    public function getImageComments($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $image = $this->mDb->getImageById($id);
            if ($image->user_id == $auth->getAuthenticatedUser()->id) {
                $this->mDb->markCommentsAsRead($id);
            }

            $comments = $this->mDb->getImageComments($id);
            $data = array();

            if ($image != null && $comments != null) {
                foreach ($comments as $comment) {
                    $row = array();
                    $row["id"] = $comment->id;
                    $row["datetime"] = $comment->datetime;
                    $row["comment"] = $comment->comment;
                    $row["is_can_deleted"] = ($image->user_id == $auth->getAuthenticatedUser()->id) ||
                    ($comment->user_id == $auth->getAuthenticatedUser()->id);

                    if (($image->user_id != $comment->user_id) ||
                    ($image->contest_status == Contest::STATUS_CLOSE)) {
                        if (isset($comment->avatar) && strlen($comment->avatar) > 0) {
                            $row["avatar"] = $comment->avatar;
                        }
                        $row["user_id"] = $comment->user_id;
                        $row["display_name"] = $comment->display_name;
                    }

                    $data[] = $row;
                }
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Добавления комментария к фотографии
     * @param int $id id фотографии
     */
    public function addImageComments($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            if (!isset($_POST["comment"])) {
                throw new CommentException("Комментарий не может быть пустым");
            }
            $comment = $_POST["comment"];
            $image = $this->mDb->getImageById($id);
            if (!$image) {
                throw new CommentException("Несуществующая фотография");
            }

            $this->mDb->addImageComments($id, $auth->getAuthenticatedUser()->id, $comment);
            if ($image->user_id != $auth->getAuthenticatedUser()->id) {
                $this->gcmSendMessage($image->user_id);
            }
            
            $ach = new AchievementsMgmt();
            $ach->setDbConnection($this->mDb);
            // Оставить 50 комментариев
            $ach->incOrGiveBadge($auth->getAuthenticatedUser(), AchievementsMgmt::A19);
            // Оставить 200 комментариев
            $ach->incOrGiveBadge($auth->getAuthenticatedUser(), AchievementsMgmt::A20);
        }
    }

    /**
     * Удаление комментария.
     * Комментарий удалить может только автор комментария или автор фотографии, под работой которого
     * оставлен комментарий
     * @param unknown $id
     */
    public function removeComment($id) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $comment = $this->mDb->getImageCommentById($id);
            if (!$comment) {
                throw new CommentException("Попытка удаления несуществующего комментария");
            }

            $image = $this->mDb->getImageById($comment->image_id);
            if (!$image) {
                throw new CommentException("Несуществующая фотография");
            }

            $perm = ($image->user_id == $auth->getAuthenticatedUser()->id) ||
            ($comment->user_id == $auth->getAuthenticatedUser()->id);

            if (!$perm) {
                throw new CommentException("У вас нет прав на удаление этого комментария");
            }

            $this->mDb->removeComment($id, $image->id);
            $ach = new AchievementsMgmt();
            $ach->setDbConnection($this->mDb);
            // Оставить 50 комментариев
            $ach->decVal($auth->getAuthenticatedUser(), AchievementsMgmt::A19);
            // Оставить 200 комментариев
            $ach->decVal($auth->getAuthenticatedUser(), AchievementsMgmt::A20);
        }
    }
}
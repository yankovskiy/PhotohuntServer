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

require_once 'vendor/autoload.php';
require_once 'include/usermgmt.php';
require_once 'include/contestmgmt.php';
require_once 'include/common.php';
require_once 'include/exceptions.php';
require_once 'include/shopmgmt.php';
require_once 'include/messagemgmt.php';

$app = new \Slim\Slim();
$app->contentType('text/html; charset=utf-8');
$app->notFound(function () use ($app) {
    $app->halt(404);
});

    /* user management */
    $app->get('/user/:id', 'getUser');                     // получение информации о пользователе
    $app->put('/user/:id', 'updateUser');                  // изменение информации о пользователе
    $app->post('/user', 'addUser');                        // регистрация пользователя
    $app->delete('/user/:id', 'deleteUser');               // удаление пользователя
    $app->put('/reset', 'generateHash');                   // генерация ссылки для сброса пароля
    $app->get('/reset/:cryptUser/:hash', 'sendPassword');  // сброс пароля
    $app->get('/user', 'getRating');                       // рейтинг пользователей (top 10)
    $app->get('/user/:id/images', 'getUserImages');        // получение списка всех картинок пользователя
    $app->post('/avatar', 'addAvatar');                    // добавление аватара пользователем
    $app->delete('/avatar', 'deleteAvatar');               // удаление аватара пользователя
    $app->get('/user/:id/stats', 'getUserStats');          // получение статистики по пользователю
    $app->get('/user/:id/wins', 'getWinsList');            // получение списка тем в которых победил пользователь

    /* contest management */
    $app->get('/contests', 'getContests');            // список всех конкурсов
    $app->get('/contest', 'getOpenContests');         // получить информацию об открытых конкурсах
    $app->get('/contest/:id', 'getContestDetails');   // получить детальную информацию по указанном конкурсе
    $app->post('/contest/:id', 'addImageToContest');  // добавить фотографию в открытый конкурс
    $app->put('/contest/:id', 'voteForContest');      // голосование за изображение
    $app->delete('/image/:id', 'deleteImage');        // удаление своего изображения
    $app->put('/image/:id', 'updateImage');           // изменение информации о своем изображении

    /* shop management */
    $app->get('/shop', 'getShop');                    // список всех продоваемых товаров
    $app->get('/shop/my', 'getMyItems');              // список предметов пользователя
    $app->post('/shop/:id', 'buyItem');               // покупка предмета
    $app->put('/shop/my/:id', 'useItem');             // использование предмета

    /* message management */
    $app->get('/messages', 'getMyMessages');          // получение списка своих сообщений
    $app->put('/messages/:id', 'readMessage');        // прочитать сообщение
    $app->delete('/messages/:id', 'removeMessage');   // удалить сообщение
    $app->post('/messages', 'sendMessage');           // отправить сообщение
    
    /* favorites user management */
    $app->get('/favorites/users', 'getFavoritesUsers');        // Получить список любимых авторов
    $app->put('/favorites/users/:id', 'updateFavoriteUser');   // Добавить / убрать любимого автора

    $app->run();
    
    function getFavoritesUsers() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->getFavoritesUsers();
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }

        } catch (PDOException $e) {
            $app->halt(500);
        }
    }
    
    function updateFavoriteUser($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        
        try {
            $user->connectToDb();
            try {
                $user->updateFavoriteUser($id);
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }
    
    
    function sendMessage() {
        $msg = new MessageMgmt();
        $app = \Slim\Slim::getInstance();
        
        try {
            $msg->connectToDb();
            try {
                $msg->sendMessage($app->request()->getBody());
            } catch (MessageException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getMyMessages() {
        $msg = new MessageMgmt();
        $app = \Slim\Slim::getInstance();

        try {
            $msg->connectToDb();
            try {
                $msg->getMyMessages();
            } catch (MessageException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }
    
    function readMessage($id) {
        $msg = new MessageMgmt();
        $app = \Slim\Slim::getInstance();
        
        try {
            $msg->connectToDb();
            try {
                $msg->readMessage($id);
            } catch (MessageException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }
    
    function removeMessage($id) {
        $msg = new MessageMgmt();
        $app = \Slim\Slim::getInstance();
    
        try {
            $msg->connectToDb();
            try {
                $msg->removeMessage($id);
            } catch (MessageException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getWinsList($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->getWinsList($id);
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getUserStats($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->getUserStats($id);
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function addAvatar() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->addAvatar();
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function deleteAvatar() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->deleteAvatar();
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }


    function getShop() {
        $shop = new ShopMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $shop->connectToDb();
            try {
                $shop->getShop();
            } catch (ShopException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getMyItems() {
        $shop = new ShopMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $shop->connectToDb();
            try {
                $shop->getMyItems();
            } catch (ShopException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function buyItem($id) {
        $shop = new ShopMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $shop->connectToDb();
            try {
                $shop->buyItem($id);
            } catch (ShopException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function useItem($id) {

    }

    function getUserImages($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            try {
                $user->getUserImages($id);
            } catch (UserException $e) {
                $error = array("status" => false, "error" => $e->getMessage());
                $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function deleteImage($id) {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            $contest->deleteImage($id);
        } catch (ContestException $e) {
            $error = array("status" => false, "error" => $e->getMessage());
            $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function updateImage($id) {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        $body = $app->request()->getBody();
        try {
            $contest->conenctToDb();
            $contest->updateImage($id, $body);
        } catch (ContestException $e) {
            $error = array("status" => false, "error" => $e->getMessage());
            $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getRating() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        try {
            $user->connectToDb();
            if (Common::getClientVersion() < 25) {
                $user->getRating_api25();
            } else {
                $user->getRating();
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getUser($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            if ($user->getUser($id) == false) {
                $app->halt(404);
            }

        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function addUser() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();

        try {
            $user->connectToDb();
            if ($user->addUser($app->request()->getBody())) {
                $app->halt(200);
            } else {
                $app->halt(403);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function updateUser($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        $body = $app->request()->getBody();

        try {
            $user->connectToDb();
            if ($user->updateUser($id, $body)) {
                $app->halt(200);
            } else {
                $app->halt(403);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function deleteUser($id) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        try {
            $user->connectToDb();
            if ($user->deleteUser($id)) {
                $app->halt(200);
            } else {
                $app->halt(403);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getContests() {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            if($contest->getContests() == false) {
                $app->halt(403);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getContestDetails($id) {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            if($contest->getContestDetails($id) == false) {
                $app->halt(404);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function addImageToContest($id) {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            $status = $contest->addImageToContest($id);
            if($status["status"] == false) {
                $app->halt(403, json_encode($status, JSON_UNESCAPED_UNICODE));
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function voteForContest($id) {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            $body = $app->request()->getBody();
            if (Common::getClientVersion() < 13) {
                $status = $contest->voteForContest_api13($id, $body);
                if($status["status"] == false) {
                    $app->halt(403, json_encode($status, JSON_UNESCAPED_UNICODE));
                }
            } else {
                try{
                    $contest->voteForContest($id, $body);
                } catch (ContestException $e) {
                    $error = array("status" => false, "error" => $e->getMessage());
                    $app->halt(403, json_encode($error, JSON_UNESCAPED_UNICODE));
                }
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function generateHash() {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        try {
            $user->connectToDb();
            $body = $app->request()->getBody();
            if ($user->generateHash($body)) {
                $app->halt(200);
            } else {
                $app->halt(404);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function sendPassword($cryptUser, $hash) {
        $user = new UserMgmgt();
        $app = \Slim\Slim::getInstance();
        try {
            $user->connectToDb();
            if ($user->sendPassword($cryptUser, $hash) == false) {
                $app->halt(404);
            }
        } catch (PDOException $e) {
            $app->halt(500);
        }
    }

    function getOpenContests() {
        $contest = new ContestMgmt();
        $app = \Slim\Slim::getInstance();
        try {
            $contest->conenctToDb();
            if (Common::getClientVersion() < 8) {
                if ($contest->getLastContest() == false) {
                    $app->halt(404);
                }
            } else {
                if ($contest->getOpenContests() == false) {
                    $app->halt(404);
                }
            }

        } catch (PDOException $e) {
            $app->halt(500);
        }
    }
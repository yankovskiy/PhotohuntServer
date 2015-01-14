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

/* contest management */
$app->get('/contests', 'getContests');            // список всех конкурсов
$app->get('/contest', 'getLastContest');          // получить информацию о последнем конкурсе
$app->get('/contest/:id', 'getContestDetails');   // получить детальную информацию по указанном конкурсе
$app->post('/contest/:id', 'addImageToContest');  // добавить фотографию в открытый конкурс
$app->put('/contest/:id', 'voteForContest');      // голосование за изображение

$app->run();

function getRating() {
    $user = new UserMgmgt();
    $app = \Slim\Slim::getInstance();
    try {
        $user->connectToDb();
        $user->getRating();
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
        if($contest->addImageToContest($id) == false) {
            $app->halt(403);
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
        if($contest->voteForContest($id, $body) == false) {
            $app->halt(403);
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

function getLastContest() {
    $contest = new ContestMgmt();
    $app = \Slim\Slim::getInstance();
    try {
        $contest->conenctToDb();
        if($contest->getLastContest() == false) {
            $app->halt(404);
        }
        
    } catch (PDOException $e) {
        $app->halt(500);
    }
}
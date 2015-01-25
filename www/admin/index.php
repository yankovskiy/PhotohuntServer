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

require_once '../include/db.php';
require_once '../include/auth.php';
require_once '../vendor/smarty/Smarty.class.php';
require_once '../include/contestmgmt.php';

abstract class Types {
    const CONTEST = "contest";
    const IMAGE = "image";
    const USER = "user";
}

abstract class Action {
    const VIEW = "view";
    const ADD = "add";
    const EDIT = "edit";
    const DELETE = "delete";
}

class Admin {
    private $mDb;

    private function getType() {
        if(isset($_GET["type"])) {
            return $_GET["type"];
        } else {
            return null;
        }
    }

    private function getAction() {
        if(isset($_GET["action"])) {
            return $_GET["action"];
        } else {
            return null;
        }
    }

    private function getId() {
        if(isset($_GET["id"])) {
            return $_GET["id"];
        } else {
            return null;
        }
    }

    private function getScriptPath() {
        return $_SERVER["PHP_SELF"];
    }

    private function handleContest($action) {
        $id = $this->getId();
        if ($id != null) {
            if ($action == Action::VIEW) {
                $this->viewContest($id);
            } else if ($action == Action::EDIT) {
                $this->editContest($id);
            } else if ($action == Action::DELETE) {
                $this->deleteContest($id);
            }
        } else if ($action == Action::ADD) {
            $this->createContest();
        }
    }

    private function viewContest($id) {
        $images = $this->objectsToArray($this->mDb->getImagesForContest($id, true));
        $imageList = array();
        foreach ($images as $image) {
            $val = array();
            $val["image"] = $image;
            $val["votes"] = $this->mDb->getVoteListForImage($image["id"]);
            $imageList[] = $val;
        }

        $user_option_selected = 1; // System user id
        $user_option_values = array();
        $user_option_output = array();
        foreach ($this->mDb->getUsers() as $user) {
            $user_option_values[] = $user->id;
            $user_option_output[] = $user->display_name;
        }
        
        $smarty = new Smarty();
        $smarty->assign("user_option_selected", $user_option_selected);
        $smarty->assign("user_option_values", $user_option_values);
        $smarty->assign("user_option_output", $user_option_output);
        $smarty->assign("contestId", $id);
        $smarty->assign("images", $imageList);
        $smarty->display("view_contest.tpl");
    }

    private function handleImage($action) {
        $id = $this->getId();
        $contestId = $_GET["contest"];
        if ($id != null) {
            if ($action == Action::EDIT) {
                $this->editImage($id, $contestId);
            } else if ($action == Action::DELETE) {
                $this->removeImage($id, $contestId);
            } 
        }
        else if ($action == Action::ADD) {
            $this->addImage($contestId);
        }
    }
    
    private function addImage($contestId) {
        if (isset($_POST["submit"])) {
            $image = new Image();
            $image->user_id = $_POST["user_id"];
            $image->subject = $_POST["subject"];
            $image->contest_id = $contestId;
            
            $contestMgmt = new ContestMgmt();
            $contestMgmt->setDbConnection($this->mDb);
            $contestMgmt->adminAddImage($image);
        }
        
        $this->viewContest($contestId);
    }

    private function editImage($id, $contestId) {
        if (isset($_POST["submit"])) {
            $imageInfo = new Image();
            $imageInfo->id = $id;
            $imageInfo->subject = $_POST["subject"];
            $imageInfo->user_id = $_POST["user_id"];
            $imageInfo->vote_count = $_POST["vote_count"];
            $this->mDb->updateImage($imageInfo);
        }

        $image = $this->objectsToArray($this->mDb->getImageById($id));
        $user_option_selected = $image["user_id"];
        $user_option_values = array();
        $user_option_output = array();
        foreach ($this->mDb->getUsers() as $user) {
            $user_option_values[] = $user->id;
            $user_option_output[] = $user->display_name;
        }

        $smarty = new Smarty();
        $smarty->assign("image", $image);
        $smarty->assign("user_option_selected", $user_option_selected);
        $smarty->assign("user_option_values", $user_option_values);
        $smarty->assign("user_option_output", $user_option_output);
        $smarty->display("edit_image.tpl");
    }

    private function removeImage($id, $contestId) {
        $fileName = Config::UPLOAD_PATH . $id . ".jpg";
        unlink($fileName);
        $image = $this->mDb->getImageById($id);
        $this->mDb->removeImageFromContest($contestId, $id);
        $this->mDb->decreaseUserBalance($image->user_id);
        $this->viewContest($contestId);
    }

    private function deleteContest($id) {
        $this->removeAllImagesInContest($id);
        $this->mDb->deleteContest($id);
        $this->printAllContest();
    }

    private function removeAllImagesInContest($id) {
        foreach ($this->mDb->getImagesForContest($id, false) as $image) {
            $fileName = Config::UPLOAD_PATH . $image->id . ".jpg";
            unlink($fileName);
        }
    }

    private function editContest($id) {
        if (isset($_POST["submit"])) {
            $contestInfo = new Contest();
            $contestInfo->id = $id;
            $contestInfo->subject = $_POST["subject"];
            $contestInfo->close_date = $_POST["close_date"];
            $contestInfo->user_id = $_POST["user_id"];
            $contestInfo->status = $_POST["status"];
            $contestInfo->rewards = $_POST["rewards"];
            $this->mDb->updateContest($contestInfo);
        }
        $contest = $this->objectsToArray($this->mDb->getContest($id));
        $user_option_selected = $contest["user_id"];
        $user_option_values = array();
        $user_option_output = array();
        foreach ($this->mDb->getUsers() as $user) {
            $user_option_values[] = $user->id;
            $user_option_output[] = $user->display_name;
        }

        $status_option_selected = $contest["status"];
        $status_option_values = array(Contest::STATUS_CLOSE, Contest::STATUS_OPEN, Contest::STATUS_VOTES);
        $status_option_output = array("Закрыт", "Прием работ", "Голосование");

        $smarty = new Smarty();
        $smarty->assign("contest", $contest);
        $smarty->assign("user_option_selected", $user_option_selected);
        $smarty->assign("user_option_values", $user_option_values);
        $smarty->assign("user_option_output", $user_option_output);
        $smarty->assign("status_option_selected", $status_option_selected);
        $smarty->assign("status_option_values", $status_option_values);
        $smarty->assign("status_option_output", $status_option_output);
        $smarty->display("edit_contest.tpl");
    }

    private function createContest() {
        if (isset($_POST["submit"])) {
            $contestInfo = new Contest();
            $contestInfo->subject = $_POST["subject"];
            $contestInfo->close_date = $_POST["close_date"];
            $contestInfo->user_id = $_POST["user_id"];
            $contestInfo->status = $_POST["status"];
            $contestInfo->rewards = $_POST["rewards"];
            $this->mDb->addContest($contestInfo);
            $this->printAllContest();
        } else {
            $user_option_selected = 1; // System user id
            $user_option_values = array();
            $user_option_output = array();
            foreach ($this->mDb->getUsers() as $user) {
                $user_option_values[] = $user->id;
                $user_option_output[] = $user->display_name;
            }

            $status_option_selected = Contest::STATUS_OPEN;
            $status_option_values = array(Contest::STATUS_CLOSE, Contest::STATUS_OPEN, Contest::STATUS_VOTES);
            $status_option_output = array("Закрыт", "Прием работ", "Голосование");

            $close_date = date('Y-m-d', strtotime("+3 days"));

            $smarty = new Smarty();
            $smarty->assign("close_date", $close_date);
            $smarty->assign("user_option_selected", $user_option_selected);
            $smarty->assign("user_option_values", $user_option_values);
            $smarty->assign("user_option_output", $user_option_output);
            $smarty->assign("status_option_selected", $status_option_selected);
            $smarty->assign("status_option_values", $status_option_values);
            $smarty->assign("status_option_output", $status_option_output);
            $smarty->display("add_contest.tpl");
        }
    }

    private function printAllContest() {
        $smarty = new Smarty();
        $contests = $this->objectsToArray($this->mDb->getContests(true));
        $smarty->assign("contests", $contests);
        $smarty->display("contests.tpl");
    }

    private function objectsToArray($object) {
        return json_decode(json_encode($object,JSON_UNESCAPED_UNICODE), true);
    }

    private function handleUser($action) {
        $id = $this->getId();
        if ($id != null) {
            if ($action == Action::EDIT) {
                $this->editUser($id);
            } else if ($action == Action::DELETE) {
                $this->removeUser($id);
            }
        } else {
            if ($action == Action::ADD) {
                $this->createUser();
            } else if ($action == Action::VIEW) {
                $this->printAllUsers();
            }
        }
    }

    private function editUser($id) {
        if (isset($_POST["submit"])) {
            $user = new User();
            $user->id = $id;
            $user->balance = $_POST["balance"];
            $user->display_name = $_POST["display_name"];
            $user->group = $_POST["group"];
            $user->user_id = $_POST["user_id"];
            $user->vote_count = $_POST["vote_count"];

            if (strlen($_POST["password"]) > 0) {
                $user->password = Auth::crypt($user->user_id, $_POST["password"]);
            }
            $this->mDb->adminUpdateUser($user);
        }

        $user = $this->objectsToArray($this->mDb->getUserById($id));
        $group_option_values = array("users", "admin");
        $group_option_selected = $user["group"];
        $group_option_output = array("users", "admin");

        $smarty = new Smarty();
        $smarty->assign("user", $user);
        $smarty->assign("group_option_values", $group_option_values);
        $smarty->assign("group_option_selected", $group_option_selected);
        $smarty->assign("group_option_output", $group_option_output);
        $smarty->display("edit_user.tpl");
    }

    private function createUser() {
        if (isset($_POST["submit"])) {
            $user = new User();
            $user->balance = $_POST["balance"];
            $user->display_name = $_POST["display_name"];
            $user->group = $_POST["group"];
            $user->user_id = $_POST["user_id"];
            $user->vote_count = $_POST["vote_count"];
            $user->password = Auth::crypt($user->user_id, $_POST["password"]);

            $this->mDb->adminAddUser($user);
            $this->printAllUsers();
        } else {
            $group_option_values = array("users", "admin");
            $group_option_selected = "users";
            $group_option_output = array("users", "admin");

            $smarty = new Smarty();
            $smarty->assign("group_option_values", $group_option_values);
            $smarty->assign("group_option_selected", $group_option_selected);
            $smarty->assign("group_option_output", $group_option_output);
            $smarty->display("add_user.tpl");
        }
    }

    private function removeUser($id) {
        $this->mDb->deleteUserById($id);
        $this->printAllUsers();
    }

    private function printAllUsers() {
        $smarty = new Smarty();
        $users = $this->objectsToArray($this->mDb->getUsers());
        $count = count($users);
        $smarty->assign("users", $users);
        $smarty->assign("count", $count);
        $smarty->display("view_users.tpl");
    }

    public function run() {
        $this->mDb = new Database();
        try {
            $this->mDb->connect();
            $auth = new Auth();
            if($auth->adminAuthenticate($this->mDb) == false) {
                throw  new Exception("Ошибка авторизации");
            }

            if ($this->getType() == Types::CONTEST) {
                $this->handleContest($this->getAction());
            } else if ($this->getType() == Types::IMAGE){
                $this->handleImage($this->getAction());
            } else if ($this->getType() == Types::USER) {
                $this->handleUser($this->getAction());
            } else {
                $this->printAllContest();
            }

        } catch (PDOException $e) {
            //printf("%s\n", $e->getMessage());
        } catch (Exception $e) {
            printf("<strong>%s</strong>\n", $e->getMessage());
        }
    }
}

$admin = new Admin();
$admin->run();
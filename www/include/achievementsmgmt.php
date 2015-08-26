<?php
require_once 'db.php';

class AchievementsMgmt {
    const ACHIEVED = -100;
    const A1 = "a1"; // complete
    const A2 = "a2"; // complete 
    const A3 = "a3"; // complete
    const A4 = "a4"; // complete
    const A5 = "a5"; // complete
    const A6 = "a6"; // complete
    const A7 = "a7"; // complete
    const A8 = "a8"; // complete
    const A9 = "a9"; // complete
    const A10 = "a10";
    const A11 = "a11";
    const A12 = "a12";
    const A13 = "a13"; // complete
    const A14 = "a14"; // complete
    const A15 = "a15"; // complete
    const A16 = "a16"; // complete
    const A17 = "a17"; // complete
    const A18 = "a18"; // complete
    const A19 = "a19"; // complete
    const A20 = "a20"; // complete


    private $mDb;

    /**
     * Добавление пользователю значка
     * @param User $user пользователь
     * @param String $badge название достижения
     */
    public function addBadge($user, $badge) {
        if ($this->isBadgeValid($badge)) {
            $this->mDb->setBadgeValue($user->id, $badge, self::ACHIEVED);
            $this->mDb->logBadge($user->id, $badge);
        }
    }

    public function getAchievmentMaxValue($badge) {
        if ($this->isBadgeValid($badge)) {
            return $this->mDb->getAchievementMaxValue($badge);
        } else {
            return -1;
        }
    }
    
    /**
     * Проверяет, имеет ли пользователь достижение
     * @param User $user пользователь
     * @param string $badge название достижения
     * @return boolean true если пользователь имеет достижение
     */
    public function isHaveBadge($user, $badge) {
        $usr = (array) $user;
        return $this->isBadgeValid($badge) && $usr[$badge] == self::ACHIEVED;
    }

    /**
     * Получение значения достижения у пользователя
     * @param User $user пользователь
     * @param string $badge название достижения
     * @return int значение достижения, либо -1 в случае ошибки
     */
    public function getUserBadgeValue($user, $badge) {
        if ($this->isBadgeValid($badge)) {
            $usr = (array) $user;
            return $usr[$badge];
        } else {
            return -1;
        }
    }
    
    /**
     * Установка значения для достижения у пользователя
     * @param User $user пользователь
     * @param string $badge достижение
     * @param int $value значение достижения
     */
    public function setUserBadgeValue($user, $badge, $value) {
        if ($this->isBadgeValid($badge)) {
            $this->mDb->setBadgeValue($user->id, $badge, $value);
        }
    }

    /**
     * Увеличивает счетчик в целочисленном достижении, или выдает награду
     * @param User $user пользователь для которого осуществить операцию
     * @param string $badge название значка
     */
    public function incOrGiveBadge($user, $badge) {
        if ( ($val = $this->getUserBadgeValue($user, $badge)) != AchievementsMgmt::ACHIEVED) {
            $usr = (array) $user;
            if (++$usr[$badge] < $this->getAchievmentMaxValue($badge)) {
                $this->setUserBadgeValue($user, $badge, ++$val);
            } else {
                $this->addBadge($user, $badge);
            }
        }
    }
    
    /**
     * Уменьшение счетчика в целочисленном достижении
     * @param User $user пользователь для которого осуществить операцию
     * @param string $badge название значка
     */
    public function decVal($user, $badge) {
        if ( ($val = $this->getUserBadgeValue($user, $badge)) != AchievementsMgmt::ACHIEVED) {
            $usr = (array) $user;
            if (--$usr[$badge] >= 0) {
                $this->setUserBadgeValue($user, $badge, --$val);
            } 
        }
    }
    
    /**
     * Проверяет, является ли награда корректным значением
     * @param string $badge название награды
     * @return boolean true если награда корректна
     */
    public function isBadgeValid($badge) {
        $isVaild = true;

        switch ($badge) {
            case self::A1:
                break;
            case self::A2:
                break;
            case self::A3:
                break;
            case self::A4:
                break;
            case self::A5:
                break;
            case self::A6:
                break;
            case self::A7:
                break;
            case self::A8:
                break;
            case self::A9:
                break;
            case self::A10:
                break;
            case self::A11:
                break;
            case self::A12:
                break;
            case self::A13:
                break;
            case self::A14:
                break;
            case self::A15:
                break;
            case self::A16:
                break;
            case self::A17:
                break;
            case self::A18:
                break;
            case self::A19:
                break;
            case self::A20:
                break;
            default:
                $isVaild = false;
        }

        return $isVaild;
    }

    /**
     * Получить список всех доступных достижений
     */
    public function getAchievements() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = (array) $auth->getAuthenticatedUser();
            $achs = $this->mDb->getAchievements();
            $achievementsStats = $this->mDb->getAchievementsStats();
            $achievementsCount = count($achs);

            $achievements = array();
            for ($i = 0; $i < $achievementsCount; $i++) {
                $achievements[$i]["service_name"] = $achs[$i]["service_name"];
                $achievements[$i]["name"] = $achs[$i]["name"];
                $achievements[$i]["description"] = $achs[$i]["description"];
                $achievements[$i]["status"] = ($user[$achievements[$i]["service_name"]] == self::ACHIEVED);
                $achievements[$i]["count"] = $achievementsStats[$achievements[$i]["service_name"]];
            }

            echo json_encode($achievements, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получить список достижений пользователя
     * @param int $userId id пользователя, либо 0 если получить нужно данные об авторизовавшемся 
     * пользователе
     */
    public function getUserAchievements($userId) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = null;
            if ($userId == 0) {
                $user = $auth->getAuthenticatedUser();
            } else {
                $user = $this->mDb->getUserById($userId);
            }
            
            if ($user == null) {
                throw new AchievementsException("Неизвестный пользователь");
            }
            
            $user = (array) $user;
            $achievements = $this->mDb->getAchievements();
            for ($i = 0; $i < count($achievements); $i++) {
                $achievements[$i]["status"] = ($user[$achievements[$i]["service_name"]] == self::ACHIEVED);
                $achievements[$i]["count"] = $user[$achievements[$i]["service_name"]];
            }
            
            echo json_encode($achievements, JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Получить список пользователей получивших указанное достижение
     * @param string $achievement системное название достижения
     * @throws AchievementsException
     */
    public function getAchievementUserList($achievement) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            if (!$this->isBadgeValid($achievement)) {
                throw new AchievementsException("Некорректное значение достижения");
            }
            
            $users = $this->mDb->getAchievementUserList($achievement);
            echo json_encode($users, JSON_UNESCAPED_UNICODE);
        }
    }
    
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
}
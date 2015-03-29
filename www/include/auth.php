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

/**
 * Класс для авторизации пользователей
 */
class Auth {
    private $mUserId;
    private $mGroup;
    private $mAuthUser;
    
    /**
     * Функция для проведения авторизации
     * @param Db $db ссылка на объект для работы с базой данных
     * @return boolean true если авторизация прошла успеша
     */
    public function authenticate($db) {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $this->mUserId = $_SERVER['PHP_AUTH_USER'];
            $pass = $_SERVER['PHP_AUTH_PW'];
            $userInfo = $db->getUserByUserId($this->mUserId);

            if (isset($userInfo)) {
                if($this->isPasswordValid($userInfo, $pass)) {
                    $this->mAuthUser = $userInfo;
                    return true;
                }
            }
        }
        header('WWW-Authenticate: Basic realm="Restricted area"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }
    
    /**
     * Получить объект содержащий информацию об авторизовавшемся пользователе
     * @return User авторизовавшийся пользователь
     */
    public function getAuthenticatedUser() {
        return $this->mAuthUser;
    }
    
    /**
     * Функция для проведения авторизации в админку
     * @param Db $db ссылка на объект для работы с базой данных
     * @return boolean true если авторизация прошла успеша
     */
    public function adminAuthenticate($db) {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $this->mUserId = $_SERVER['PHP_AUTH_USER'];
            $pass = $_SERVER['PHP_AUTH_PW'];
            $userInfo = $db->getUserByUserId($this->mUserId);
    
            if (isset($userInfo)) {
                if($this->isPasswordValid($userInfo, $pass) && $userInfo->group == "admin") {
                    return true;
                }
            }
        }
        header('WWW-Authenticate: Basic realm="Restricted area"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    /**
     * Функция сверяет пароль введеный пользователем и хранящейся в базе
     * @param User $userInfo информация из базы данных
     * @param string $password пароль переданный пользователем
     * @return boolean true если пароль корректный
     */
    public function isPasswordValid($userInfo, $password) {
        return $userInfo->password == self::crypt($userInfo->user_id, $password);
    }
    
    /**
     * @return string user_id вошедшего пользователя 
     */
    public function getAuthenticatedUserId() {
        return $this->mUserId;
    }
    
    /**
     * Выполняет шифрование пароля
     * @param string $user_id пользователь
     * @param string $password пароль пользователея
     * @return шифрованный пароль пользователя
     */
    public static function crypt($user_id, $password) {
        return sha1(md5($user_id) . $password);
    }
}
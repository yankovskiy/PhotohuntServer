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
 * Класс содержащий запись из таблицы users
 */
class User {
    public $id;
    public $user_id;
    public $display_name;
    public $password;
    public $balance;
    public $hash;
    public $group;
    public $money;
    public $dc;
    public $insta;
    public $avatar;
    public $regid;
    public $client_version;
    public $a1;
    public $a2;
    public $a3;
    public $a4;
    public $a5;
    public $a6;
    public $a7;
    public $a8;
    public $a9;
    public $a10;
    public $a11;
    public $a12;
    public $a13;
    public $a14;
    public $a15;
    public $a16;
    public $a17;
    public $a18;
    public $a19;
    public $a20;
    public $today;
    
    /**
     * Конструктор
     * @param array $row содержит массив для заполнения полей объекта, либо NULL, в случае, если
     * поля заполнять не нужно
     */
    function __construct($row = NULL) {
        if (isset($row)) {
            $this->id = $row["id"];
            $this->user_id = $row["user_id"];
            $this->display_name = $row["display_name"];
            $this->password = $row["password"];
            $this->balance = $row["balance"];
            $this->hash = $row["hash"];
            $this->group = $row["group"];
            $this->money = $row["money"];
            $this->dc = $row["dc"];
            $this->insta = $row["insta"];
            $this->avatar = $row["avatar"];
            $this->regid = $row["regid"];
            $this->client_version = $row["client_version"];
        }
    }

}
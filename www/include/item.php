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

class Item {
    const EXTRA_PHOTO = "extra_photo";
    const AVATAR = "avatar";
    const EXTRA_CONTEST = "extra_contest";
    const PREMIUM7 = "premium7";
    const PREMIUM30 = "premium30";
    
    public $id;
    public $name;
    public $service_name;
    public $description;
    public $count;
    public $auto_use;
    
    /**
     * Конструктор
     * @param array $row содержит массив для заполнения полей объекта, либо NULL, в случае, если
     * поля заполнять не нужно
     */
    function __construct($row = NULL) {
        if (isset($row)) {
            $this->id = $row["id"];
            $this->name = $row["name"];
            $this->service_name = $row["service_name"];
            $this->description = $row["description"];
            $this->count = $row["count"];
            $this->auto_use = $row["auto_use"];
        }
    }
}
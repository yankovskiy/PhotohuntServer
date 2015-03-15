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
 * Класс содержащий запись из представления view_images
 */
class Image{
    public $id;
    public $contest_id;
    public $user_id;
    public $subject;
    public $display_name;
    public $vote_count;
    public $is_editable;
    public $is_voted;
    public $contest_status;
    public $contest_subject;
    public $must_win;
    
    /**
     * Конструктор
     * @param array $row содержит массив для заполнения полей объекта, либо NULL, в случае, если
     * @param boolean $isAdmin - true если к объекту будет обращение из админки (включение дополнительных полей в объект)
     * поля заполнять не нужно
     */
    function __construct($row = NULL, $isAdmin = false) {
        if (isset($row)) {
            $this->id = $row["id"];
            $this->contest_id = $row["contest_id"];
            $this->user_id = $row["user_id"];
            $this->subject = $row["subject"];
            $this->display_name = $row["display_name"];
            $this->vote_count = $row["vote_count"];
            
            if ($isAdmin) {
                $this->must_win = $row["must_win"];
            }
        }
    }
}
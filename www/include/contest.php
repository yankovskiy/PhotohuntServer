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
 * Класс содержащий запись из представления view_contests
 */
class Contest {
    const STATUS_CLOSE = 0;
    const STATUS_OPEN = 1;
    const STATUS_VOTES = 2;
    
    const MAX_VOTES = 3;
    
    public $id;
    public $subject;
    public $rewards;
    public $open_date;
    public $close_date;
    public $status;
    public $user_id;
    public $display_name;
    public $works;
    public $prev_id;
    public $avatar;

    /**
     * Конструктор
     * @param array $row содержит массив для заполнения полей объекта, либо NULL, в случае, если
     * поля заполнять не нужно
     */
    function __construct($row = NULL) {
        if (isset($row)) {
            $this->id = $row["id"];
            $this->subject = $row["subject"];
            $this->rewards = $row["rewards"];
            $this->open_date = $row["open_date"];
            $this->close_date = $row["close_date"];
            $this->status = $row["status"];
            $this->user_id = $row["user_id"];
            $this->display_name = $row["display_name"];
            $this->works = $row["works"];
            $this->prev_id = $row["prev_id"];
            $this->avatar = $row["avatar"];
        }
    }

}
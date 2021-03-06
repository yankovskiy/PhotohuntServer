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

require_once '../include/contest.php';
require_once '../include/contestmgmt.php';

function changeStatus () {
    try {
        $contest = new ContestMgmt();
        $contest->conenctToDb();
        $contest->changeStatusToVote();
        if($contest->changeStatusToClose()) {
            $contest->createNewContests();
        }
    } catch (PDOException $e) {

    }
}

changeStatus();

<?php
require_once '../include/db.php';

$db = new Database();
try {
    $db->connect();
    $db->copyRatingToQuarter();
} catch (PDOException $e) {
    
}